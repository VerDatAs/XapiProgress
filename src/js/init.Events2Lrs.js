let XapiProgress = {

    getScript: function (url) {

        return $.ajax({
            cache: false,
            dataType: 'script',
            url: this.iliasHttpPath + this.pluginScriptPath + url
        });

    }

};



