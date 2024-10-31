(function () {
    tinymce.create('tinymce.plugins.nbbNiftyBuyButtomPlugin', {
        // Initialize the plugin
        init: function (editor) {
            // Register command
            editor.addCommand('openPopUp', function () {

                jQuery('#nbb-link-wrap, #nbb-link-backdrop').show();
                jQuery('#niftycart-upsert-submit').click(function (e) {
                    if( jQuery('.niftycart-products').select2('val') == '' ){
                        return false;
                    }
                    var niftycart_shortcode = '[niftycart products="' + jQuery('.niftycart-products').select2('val') + '"]';
                    editor.execCommand('mceInsertContent', false, niftycart_shortcode );
                    jQuery('.niftycart-upsert-close').trigger('click');
                });
            });

            // Register button to toolbar
            editor.addButton('nbbNiftyBuyButtomPlugin', {
                'title': 'Nifty Buy Button',
                'icon': 'nbb-blurb-mce-icon', // Do not prepand mce-i prefix to icon
                cmd: 'openPopUp'
            });
        },
        getInfo: function () {
            return {
                longname: 'Nifty Buy Button Plugin',
                author: 'Listen Softwares Inc.',
                authorurl: 'http://www.niftycart.com',
                infourl: 'http://www.niftycart.com',
                version: '3.3'
            }
        },

    });
    tinymce.PluginManager.add('nbbNiftyBuyButtomPlugin', tinymce.plugins.nbbNiftyBuyButtomPlugin);
})();