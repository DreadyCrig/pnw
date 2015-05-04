<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Charge Webhook Model class
 *
 * @package         charge_ee_addon
 * @version         1.9.2
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/software/expressionengine/charge
 * @copyright       Copyright (c) 2015, Joel Bradbury/Square Bit
 */
class Charge_webhook_model extends Charge_model
{

    private $events_map = array(
        'customer.subscription.created' => true,
        'invoice.payment_succeeded'     => true,
        'customer.subscription.deleted' => false);
    private $charge_id = null;
    private $customer_id = null;
    private $updateStripeObjectRecord = true;
    private $callType;
    private $callMode;
    private $validCallType = false;

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /*
     * Constructor
     *
     * @access      public
     * @return      void
     */
    function __construct()
    {
        // Call parent constructor
        parent::__construct();

        // Initialize this model
        $this->initialize(
            'charge_webhook',
            'call_id',
            array(
                'site_id'   => 'int(4) unsigned NOT NULL default 0',
                'timestamp' => 'int(10) unsigned NOT NULL default 0',
                'mode'      => 'varchar(100) NOT NULL default ""',
                'id'        => 'varchar(100) NOT NULL default ""',
                'type'      => 'varchar(100) NOT NULL default ""',
                'body'      => 'text',
                'status'    => 'varchar(100) NOT NULL default ""')
        );
    }

    // --------------------------------------------------------------------

    /**
     * Installs given table
     *
     * @access      public
     * @return      void
     */
    public function install()
    {
        // Call parent install
        parent::install();

        // Add indexes to table
        ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`site_id`)");
        ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`type`)");


        $this->_set_key();
    }

    public function act($body)
    {
        $state = $this->_act($body);

        if ($state) {
            // We can trigger some extra actions now
            if ($this->updateStripeObjectRecord == true && $this->validCallType && $this->charge_id != null) {
                // Update the customer record with an up to date customer record
                $this->_update_customer_stripe_record();
            }
        }

        return $state;
    }

    private function _update_customer_stripe_record()
    {
        if ($this->charge_id == null || $this->customer_id == null) return;

        // Hit the api for the latest customer record version
        $customer = ee()->charge_stripe->retrieve_stripe_customer($this->customer_id);
        if ($customer == false) return;

        $customer = $customer->__toArray();

        // Encode and update
        $encoded = base64_encode(serialize($customer));


        $update['stripe'] = $encoded;
        ee()->charge_stripe->update($this->charge_id, $update);

        return true;
    }

    private function _act($body)
    {
        // Pull out the type to start
        if (!isset($body['type'])) return false;

        $type = $body['type'];
        $mode = 'test';
        if ($body['livemode'] === true) $mode = 'live';


        $this->callType = $type;
        $this->callMode = $mode;

        // Extract the customer_id
        $customer_id = $this->extract_customer_id($body);
        $invoice_value = $this->extract_invoice_value($body);

        if (ee()->extensions->active_hook('charge_webhook_received_start') === true) {
            ee()->extensions->call('charge_webhook_received_start', $type, $mode, $body);
            if (ee()->extensions->end_script === true) return;
        }


        if (!isset($this->events_map[$type])) {
            // Nothing to do really
            return true;
        }

        $this->validCallType = true;

        // We seem to have something to do
        // We must find the relevant member for this event
        $action_state = $this->events_map[$type];

        if ($customer_id === false) return false;


        // Get this customer's subscription
        if ($customer_id == 'cus_00000000000000') {
            // Dummy member, coming from test webhook
            return false;

            // Fire anyway?
            //$customer_id = 'cus_5eVSluxZ6QoY0w';
        }


        // Get the original charge that started this subscription
        $charge = ee()->charge_stripe->get_one($customer_id, 'customer_id');
        if (empty($charge)) return false;

        $this->charge_id = $charge['id'];
        $this->customer_id = $customer_id;

        // Update the stripe record here too
        $this->_update_customer_stripe_record();
        // We do this right now just in case
        // Now we need to refetch the charge again.
        // this will have the latest version of the stripe array.
        $charge = ee()->charge_stripe->get_one($customer_id, 'customer_id');


        // Handle set length plans
        if ($type == 'invoice.payment_succeeded' AND $charge['plan_length_expiry'] != '') {

            // This will kill the plan if needed.
            // If it's killed, this will trigger a fresh set of hook events
            // but that's invisible to this action, so we handle it all atomically
            ee()->charge_stripe->handle_plan_expiry($charge);
        }


        // Handle the empty invoice success we recieve when a trial plan is created
        // with a zero balance paid
        if ($type == 'invoice.payment_succeeded') {
            if ($invoice_value !== false AND $invoice_value <= 0) {
                // We have a zero balance invoice from a trial success
                return true;
            }
        }

        // Now handle any subscriptions
        $sub_member = ee()->charge_subscription_member->get_one($customer_id, 'customer_id');
        if (empty($sub_member)) return false;

        $subscription = ee()->charge_subscription->get_one($sub_member['subscription_id']);
        if (empty($subscription)) return false;

        // Based on the action state we might be removing a member subscription
        // or just sending a thank you note
        if ($action_state == true) {
            if ($type == 'invoice.payment_succeeded') $action_type = 'recurring';
            else $action_type = 'welcome';

            // Thank you note
            return $this->_handle_success($customer_id, $sub_member, $subscription, $action_type, $charge);
        } else {
            // Remove the member
            return $this->_handle_failure($customer_id, $sub_member, $subscription, $charge);
        }


        return false;
    }

    public function extract_customer_id($body = array())
    {
        if (!isset($body['data']['object']['customer'])) return false;

        return $body['data']['object']['customer'];
    }


    public function extract_invoice_value($body = array())
    {
        if (!isset($body['data']['object']['total'])) return false;

        return $body['data']['object']['total'];
    }


    private function _handle_failure($customer_id, $sub_member, $subscription, $charge)
    {
        // Move the member to the failure group
        ee()->charge_subscription->remove_member($sub_member['subscription_member_id']);

        // Now deal with emails
        $this->_email('failure', $sub_member, $subscription, $charge['hash']);

        return true;
    }

    private function _handle_success($customer_id, $sub_member, $subscription, $type = 'welcome', $charge)
    {
        // Good
        // Touch the record
        $data['last_contact'] = time();
        ee()->charge_subscription_member->update($sub_member['subscription_member_id'], $data);

        // Now deal with success conditions
        $this->_email($type, $sub_member, $subscription, $charge['hash']);

        return true;
    }


    private function _email($type, $sub_member, $subscription, $hash = '')
    {
        if (isset($subscription['settings']['email_member_' . $type . '_enabled']) AND $subscription['settings']['email_member_' . $type . '_enabled'] == 'yes') {
            // Fire email

            $subject = $subscription['settings']['email_member_' . $type . '_subject'];
            $template = $subscription['settings']['email_member_' . $type . '_template'];
            $bcc = array();
            foreach (explode(',', $subscription['settings']['email_member_' . $type . '_bcc']) as $address) {
                $bcc[] = trim($address);
            }

            $member = ee()->db->select('email')->where('member_id', $sub_member['member_id'])->from('members')->get()->row_array();
            if (empty($member)) return;

            $to = array($member['email']);

            $email = ee()->charge_email->send($hash, array($to), $subject, $subscription['settings']['email_member_' . $type . '_template']);
        }
    }

    private function _set_key()
    {
        // Also generate a unique 'charge_webhook_key' on install
        $rand_key = ee()->functions->random('alnum', 32);

        $preferences['charge_webhook_key'] = $rand_key;

        /** --------------------------------------------
         * /**    Grab prefs from DB
         * /** --------------------------------------------*/
        $site_id = ee()->config->item('site_id');

        $sql = "SELECT site_system_preferences
					FROM exp_sites
					WHERE site_id = " . ee()->db->escape_str($site_id);

        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0) return false;

        ee()->load->helper('string');

        $prefs = unserialize(base64_decode($query->row('site_system_preferences')));


        /** --------------------------------------------
         * /**    Add our prefs
         * /** --------------------------------------------*/

        foreach (explode("|", CHARGE_PREFERENCES) as $val) {
            if (isset($preferences[$val]) === true) {
                $prefs[$val] = $preferences[$val];
            }
        }


        $prefs = base64_encode(serialize($prefs));


        ee()->db->query(ee()->db->update_string(
            'exp_sites',
            array(
                'site_system_preferences' => $prefs
            ),
            array(
                'site_id' => ee()->db->escape_str($site_id)
            )
        )
        );

        return true;

    }


} // End class

/* End of file Charge_webhook_model.php */