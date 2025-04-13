

XapiProgress = jQuery.extend({
    iliasHttpPath: '',
    pluginPath: '/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/XapiProgress/src/js/',
    H5P: {}
}, XapiProgress);



jQuery.execXapiProgressScript = function(scriptFile) {

    return jQuery.ajax({
        cache: false,
        dataType: "script",
        url: XapiProgress.iliasHttpPath + XapiProgress.pluginPath + scriptFile
    });

};


if(undefined === window.XapiProgressLoaded) {

    window.XapiProgressLoaded = true;

    window.document.addEventListener('readystatechange', function (ev) {

        let parentWin = window;

        let parentDocument = window.document; //e.target; // parentWin.document;

        let UrlH5PModule = window.location.href; // parentWin.location.href;

        if ('complete' === parentDocument.readyState && typeof parentWin.H5P !== 'undefined' && parentWin.H5P.externalDispatcher) {

            $.execXapiProgressScript( 'lookupH5PLib.js' ).done(function( script, textStatus ) {

                parentWin.H5P.lookupLib = script;

            });

            parentWin.H5P.externalDispatcher.on('xAPI', function (event) {

                event.data.statement.UrlH5PModule = UrlH5PModule;

                $.ajax({
                    type: 'POST',
                    url: window.urlXapiProgressRouterGUI,
                    dataType: 'json',
                    headers: {'xAPI': 'statement'},
                    data: event.data.statement,
                });

                console.log('XapiProgress H5P Action');

                console.dir(event.data.statement);

            });
        }
    });

}

