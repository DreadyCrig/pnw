<div id="charge_container" class="mor">

	<div class="tg">
		<h2>Coupons</h2>
		<div class="alert">
			<p>Coupons can be used with one-off and recurring payments for fixed amounts or percentage discounts.</p>
		</div>

		<table class="data">
			<tbody>
				<?php foreach($coupons as $coupon) : ?>
					<tr>
						<td><?=$coupon['name']?></td>
						<td><?=$coupon['code']?></td>
						<td><a href="<?=$view_single_coupon_uri.$coupon['coupon_id']?>">View / Edit</a></td>
						<td><a class="js-delete" data-type="coupon" href="<?=$delete_coupon_uri.$coupon['coupon_id']?>">Delete</a></td>
					</tr>
				<?php endforeach ?>
			</tbody>
		</table>
		<div class="alert">
			<p><a href="<?=$add_coupon_uri?>" class="btn">Add a Coupon</a></p>
		</div>
	</div>

</div>

