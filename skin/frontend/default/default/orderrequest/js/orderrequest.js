/**
 * Override default review save method to append customer order request
 */
Review.addMethods({
    save: function() {
        if (checkout.loadWaiting!=false)
            return;

        var customerRequestForm   = $('customer-order-request');
        var customerRequestParams = Form.serialize(customerRequestForm);

        checkout.setLoadWaiting('review');

        var params = Form.serialize(payment.form);

        if (this.agreementsForm)
            params += '&'+Form.serialize(this.agreementsForm);

        if (customerRequestParams)
            params += '&'+customerRequestParams;

        params.save = true;

        var request = new Ajax.Request(
            this.saveUrl,
            {
                method:'post',
                parameters:params,
                onComplete: this.onComplete,
                onSuccess: this.onSave,
                onFailure: checkout.ajaxFailure.bind(checkout)
            }
        );
    }
});