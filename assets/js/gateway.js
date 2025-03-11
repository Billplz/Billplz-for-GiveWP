(() => {
    let settings = {};

    // Render gateway fields
    function BillplzGiveWPFields() {
        return window.wp.element.createElement(
            "div",
            {
                className: 'billplz-givewp-help-text'
            },
            window.wp.element.createElement(
                "p",
                {
                    style: {marginBottom: 0}
                },
                settings.message,
            )
        );
    }

    // Gateway object
    const BillplzGiveWPGateway = {
        id: "billplz",
        initialize() {
            settings = this.settings
        },
        Fields() {
            return window.wp.element.createElement(BillplzGiveWPFields);
        },
    };

    // Register the gateway
    window.givewp.gateways.register(BillplzGiveWPGateway);
})();
