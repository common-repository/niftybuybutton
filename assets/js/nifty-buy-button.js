jQuery(document).ready(function () {

    /**
     * Initialize the select2 dropdown on product field
     * 
     * @since 3.4
     */
    jQuery(".niftycart-products").select2({
        placeholder: 'Select Product(s)',
    });

    /**
     * Trigger - When we close the popup
     * 
     * @return void
     * @since 3.4
     */
    jQuery(document).on('click', '.niftycart-upsert-close', function (e) {
        // reset selected products
        jQuery(".niftycart-products").select2('val', '');
        // hide the backdrop and popup
        jQuery('#nbb-link-backdrop, #nbb-link-wrap').hide();
    });
});
