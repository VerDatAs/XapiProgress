if(undefined === window.XapiProgressLoaded) {

    window.XapiProgressLoaded = true;
    let endpointH5P = './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/XapiProgress/endpointH5P.php';

    window.document.addEventListener('readystatechange', function (ev) {
        let parentWin = window;

        let parentDocument = window.document; //e.target; // parentWin.document;

        let UrlH5PModule = window.location.href; // parentWin.location.href;

        if ('complete' === parentDocument.readyState && typeof parentWin.H5P !== 'undefined' && parentWin.H5P.externalDispatcher) {

            let availCIDs = parentWin.H5PIntegration.contents;

            parentWin.H5P.externalDispatcher.on('xAPI', function (event) {

                let h5pXapiStatement = event.data.statement;

                h5pXapiStatement.UrlH5PModule = UrlH5PModule;

                $.ajax({
                    type: 'POST',
                    url: endpointH5P,
                    dataType: 'json',
                    headers: { 'xAPI': 'statement' },
                    data: h5pXapiStatement,
                });
                console.log('XapiProgress H5P Action');

                // console.dir(h5pXapiStatement);

            }, this);
        }
    });
}


