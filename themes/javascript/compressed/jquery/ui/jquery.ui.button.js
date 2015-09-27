/*!
 * jQuery UI Button @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Button
 *
 * Depends:
 *	jquery.ui.core.js
 *	jquery.ui.widget.js
 */
!function(t){var e,i,n,s,o="ui-button ui-widget ui-state-default ui-corner-all",u="ui-state-hover ui-state-active ",a="ui-button-icons-only ui-button-icon-only ui-button-text-icons ui-button-text-icon-primary ui-button-text-icon-secondary ui-button-text-only",r=function(){var e=t(this).find(":ui-button");setTimeout(function(){e.button("refresh")},1)},l=function(e){var i=e.name,n=e.form,s=t([]);return i&&(s=n?t(n).find("[name='"+i+"']"):t("[name='"+i+"']",e.ownerDocument).filter(function(){return!this.form})),s};t.widget("ui.button",{options:{disabled:null,text:!0,label:null,icons:{primary:null,secondary:null}},_create:function(){this.element.closest("form").unbind("reset.button").bind("reset.button",r),"boolean"!=typeof this.options.disabled?this.options.disabled=!!this.element.propAttr("disabled"):this.element.propAttr("disabled",this.options.disabled),this._determineButtonType(),this.hasTitle=!!this.buttonElement.attr("title");var u=this,a=this.options,d="checkbox"===this.type||"radio"===this.type,h="ui-state-hover"+(d?"":" ui-state-active"),b="ui-state-focus";null===a.label&&(a.label=this.buttonElement.html()),this.buttonElement.addClass(o).attr("role","button").bind("mouseenter.button",function(){a.disabled||(t(this).addClass("ui-state-hover"),this===e&&t(this).addClass("ui-state-active"))}).bind("mouseleave.button",function(){a.disabled||t(this).removeClass(h)}).bind("click.button",function(t){a.disabled&&(t.preventDefault(),t.stopImmediatePropagation())}),this.element.bind("focus.button",function(){u.buttonElement.addClass(b)}).bind("blur.button",function(){u.buttonElement.removeClass(b)}),d&&(this.element.bind("change.button",function(){s||u.refresh()}),this.buttonElement.bind("mousedown.button",function(t){a.disabled||(s=!1,i=t.pageX,n=t.pageY)}).bind("mouseup.button",function(t){a.disabled||(i!==t.pageX||n!==t.pageY)&&(s=!0)})),"checkbox"===this.type?this.buttonElement.bind("click.button",function(){return a.disabled||s?!1:(t(this).toggleClass("ui-state-active"),void u.buttonElement.attr("aria-pressed",u.element[0].checked))}):"radio"===this.type?this.buttonElement.bind("click.button",function(){if(a.disabled||s)return!1;t(this).addClass("ui-state-active"),u.buttonElement.attr("aria-pressed","true");var e=u.element[0];l(e).not(e).map(function(){return t(this).button("widget")[0]}).removeClass("ui-state-active").attr("aria-pressed","false")}):(this.buttonElement.bind("mousedown.button",function(){return a.disabled?!1:(t(this).addClass("ui-state-active"),e=this,void t(document).one("mouseup",function(){e=null}))}).bind("mouseup.button",function(){return a.disabled?!1:void t(this).removeClass("ui-state-active")}).bind("keydown.button",function(e){return a.disabled?!1:void((e.keyCode==t.ui.keyCode.SPACE||e.keyCode==t.ui.keyCode.ENTER)&&t(this).addClass("ui-state-active"))}).bind("keyup.button",function(){t(this).removeClass("ui-state-active")}),this.buttonElement.is("a")&&this.buttonElement.keyup(function(e){e.keyCode===t.ui.keyCode.SPACE&&t(this).click()})),this._setOption("disabled",a.disabled),this._resetButton()},_determineButtonType:function(){if(this.type=this.element.is(":checkbox")?"checkbox":this.element.is(":radio")?"radio":this.element.is("input")?"input":"button","checkbox"===this.type||"radio"===this.type){var t=this.element.parents().filter(":last"),e="label[for='"+this.element.attr("id")+"']";this.buttonElement=t.find(e),this.buttonElement.length||(t=t.length?t.siblings():this.element.siblings(),this.buttonElement=t.filter(e),this.buttonElement.length||(this.buttonElement=t.find(e))),this.element.addClass("ui-helper-hidden-accessible");var i=this.element.is(":checked");i&&this.buttonElement.addClass("ui-state-active"),this.buttonElement.attr("aria-pressed",i)}else this.buttonElement=this.element},widget:function(){return this.buttonElement},destroy:function(){this.element.removeClass("ui-helper-hidden-accessible"),this.buttonElement.removeClass(o+" "+u+" "+a).removeAttr("role").removeAttr("aria-pressed").html(this.buttonElement.find(".ui-button-text").html()),this.hasTitle||this.buttonElement.removeAttr("title"),t.Widget.prototype.destroy.call(this)},_setOption:function(e,i){return t.Widget.prototype._setOption.apply(this,arguments),"disabled"===e?void(i?this.element.propAttr("disabled",!0):this.element.propAttr("disabled",!1)):void this._resetButton()},refresh:function(){var e=this.element.is(":disabled");e!==this.options.disabled&&this._setOption("disabled",e),"radio"===this.type?l(this.element[0]).each(function(){t(this).is(":checked")?t(this).button("widget").addClass("ui-state-active").attr("aria-pressed","true"):t(this).button("widget").removeClass("ui-state-active").attr("aria-pressed","false")}):"checkbox"===this.type&&(this.element.is(":checked")?this.buttonElement.addClass("ui-state-active").attr("aria-pressed","true"):this.buttonElement.removeClass("ui-state-active").attr("aria-pressed","false"))},_resetButton:function(){if("input"===this.type)return void(this.options.label&&this.element.val(this.options.label));var e=this.buttonElement.removeClass(a),i=t("<span></span>",this.element[0].ownerDocument).addClass("ui-button-text").html(this.options.label).appendTo(e.empty()).text(),n=this.options.icons,s=n.primary&&n.secondary,o=[];n.primary||n.secondary?(this.options.text&&o.push("ui-button-text-icon"+(s?"s":n.primary?"-primary":"-secondary")),n.primary&&e.prepend("<span class='ui-button-icon-primary ui-icon "+n.primary+"'></span>"),n.secondary&&e.append("<span class='ui-button-icon-secondary ui-icon "+n.secondary+"'></span>"),this.options.text||(o.push(s?"ui-button-icons-only":"ui-button-icon-only"),this.hasTitle||e.attr("title",i))):o.push("ui-button-text-only"),e.addClass(o.join(" "))}}),t.widget("ui.buttonset",{options:{items:":button, :submit, :reset, :checkbox, :radio, a, :data(button)"},_create:function(){this.element.addClass("ui-buttonset")},_init:function(){this.refresh()},_setOption:function(e,i){"disabled"===e&&this.buttons.button("option",e,i),t.Widget.prototype._setOption.apply(this,arguments)},refresh:function(){var e="rtl"===this.element.css("direction");this.buttons=this.element.find(this.options.items).filter(":ui-button").button("refresh").end().not(":ui-button").button().end().map(function(){return t(this).button("widget")[0]}).removeClass("ui-corner-all ui-corner-left ui-corner-right").filter(":first").addClass(e?"ui-corner-right":"ui-corner-left").end().filter(":last").addClass(e?"ui-corner-left":"ui-corner-right").end().end()},destroy:function(){this.element.removeClass("ui-buttonset"),this.buttons.map(function(){return t(this).button("widget")[0]}).removeClass("ui-corner-left ui-corner-right").end().button("destroy"),t.Widget.prototype.destroy.call(this)}})}(jQuery);