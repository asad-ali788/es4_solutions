// Add custom regex method to jQuery Validate (if not already included)
jQuery.validator.addMethod(
    "regex",
    function (value, element, regexp) {
        var re = new RegExp(regexp);
        return this.optional(element) || re.test(value);
    },
    "Invalid format."
);

$("#newSourcing, #newContainerItem, #newProductModal").on(
    "hidden.bs.modal",
    function () {
        var form = $(this).find("form");
        form[0].reset();
        form.validate().resetForm();
        form.find(".form-control").removeClass("is-invalid");
    }
);

const productValidationRules = $("#updateProductForm").validate({
    rules: {
        translator: {
            required: false,
            regex: "^[A-Za-z .'-]+$",
        },
        title_amazon: {
            required: false,
            maxlength: 255,
            // regex: "^[\\w\\s\\-\\.,!?'\"()|{}\\[\\]/>]+$"

        },
        bullet_point_1: {
            required: false,
            maxlength: 250
            // regex: "^.{0,255}$", // max 255 characters, any char
        },
        bullet_point_2: {
            required: false,
            maxlength: 250
            // regex: "^.{0,255}$",
        },
        bullet_point_3: {
            required: false,
            maxlength: 250
            // regex: "^.{0,255}$",
        },
        bullet_point_4: {
            required: false,
            maxlength: 250
            // regex: "^.{0,255}$",
        },
        bullet_point_5: {
            required: false,
            maxlength: 250
            // regex: "^.{0,255}$",
        },
        description: {
            required: false,
            regex: "^.{0,1000}$", // max 1000 chars for example
        },
        search_terms: {
            required: false,
            regex: "^[\\w\\s,]+$", // words, spaces, commas
        },
        advertising_keywords: {
            required: false,
            regex: "^[\\w\\s,]+$",
        },
        product_category: {
            required: false,
            regex: "^[\\w\\s\\-]+$", // letters, numbers, spaces, dash
        },
        item_price: {
            required: false,
            number: true,
            min: 0,
            regex: "^\\d+(\\.\\d{1,2})?$", // decimal up to 2 digits
        },
        postage: {
            required: false,
            number: true,
            min: 0,
            regex: "^\\d+(\\.\\d{1,2})?$",
        },
        base_price: {
            required: false,
            number: true,
            min: 0,
            regex: "^\\d+(\\.\\d{1,2})?$",
        },
        fba_fee: {
            required: false,
            number: true,
            min: 0,
            regex: "^\\d+(\\.\\d{1,2})?$",
        },
        duty: {
            required: false,
            number: true,
            min: 0,
            regex: "^\\d+(\\.\\d{1,2})?$",
        },
        air_ship: {
            required: false,
            number: true,
            min: 0,
            regex: "^\\d+(\\.\\d{1,2})?$",
        },
        commercial_invoice_title: {
            required: false,
            regex: "^[\\w\\s\\-\\.,!?'\"()]*$",
        },
        hs_code: {
            required: false,
            regex: "^[0-9]{6}$", // HS code 6 digits
        },
        hs_code_percentage: {
            required: false,
            number: true,
            min: 0,
            regex: "^\\d+(\\.\\d{1,2})?$",
        },
        // item_size_cm: {
        //     required: false,
        //     regex: "^\\d+(\\.\\d{1,2})?$",
        // },
        // ctn_size_cm: {
        //     required: false,
        //     regex: "^\\d+(\\.\\d{1,2})?$",
        // },

        item_size_length_cm: {
            required: false,
            regex: "^\\d+(\\.\\d{1,2})?$", // numbers with optional 2 decimal places
        },
        item_size_width_cm: {
            required: false,
            regex: "^\\d+(\\.\\d{1,2})?$",
        },
        item_size_height_cm: {
            required: false,
            regex: "^\\d+(\\.\\d{1,2})?$",
        },
        ctn_size_length_cm: {
            required: false,
            regex: "^\\d+(\\.\\d{1,2})?$",
        },
        ctn_size_width_cm: {
            required: false,
            regex: "^\\d+(\\.\\d{1,2})?$",
        },
        ctn_size_height_cm: {
            required: false,
            regex: "^\\d+(\\.\\d{1,2})?$",
        },
        item_weight_kg: {
            required: false,
            number: true,
            min: 0,
            regex: "^\\d+(\\.\\d{1,3})?$",
        },
        carton_weight_kg: {
            required: false,
            number: true,
            min: 0,
            regex: "^\\d+(\\.\\d{1,3})?$",
        },
        quantity_per_carton: {
            required: false,
            number: true,
            min: 0,
            regex: "^\\d+$", // integers only
        },
        carton_cbm: {
            required: false,
            number: true,
            min: 0,
            regex: "^\\d+(\\.\\d{1,4})?$",
        },
        moq: {
            required: false,
            number: true,
            min: 0,
            regex: "^\\d+$",
        },
        product_material: {
            required: false,
            regex: "^[\\w\\s\\-]+$",
        },
        order_lead_time_weeks: {
            required: false,
            number: true,
            min: 0,
            regex: "^\\d+$",
        },
        warnings: {
            required: false,
            regex: "^.{0,500}$",
        },
        listing_to_copy: {
            required: false,
            regex: "^\\d*$", // optional numeric ID
        },
        seasonal_type: {
            required: false,
            // regex: "^\\d*$", // optional numeric ID
        },
        country: {
            required: false,
            regex: "^[A-Za-z\\s]{0,100}$",
        },
    },
    messages: {
        translator: {
            regex: "Enter a valid name in (A-Z or a-z)",
        },
        title_amazon: {
            maxlength: "Maximum 255 characters allowed",
            regex: "Contains invalid characters",
        },
        bullet_point_1: {
            required: "Bullet Point 1 is required",
            // regex: "Maximum 255 characters allowed",
        },
        bullet_point_2: {
            required: "Bullet Point 2 is required",
            // regex: "Maximum 255 characters allowed",
        },
        bullet_point_3: {
            required: "Bullet Point 3 is required",
            // regex: "Maximum 255 characters allowed",
        },
        bullet_point_4: {
            required: "Bullet Point 4 is required",
            // regex: "Maximum 255 characters allowed",
        },
        bullet_point_5: {
            required: "Bullet Point 5 is required",
            // regex: "Maximum 255 characters allowed",
        },
        description: {
            required: "Description is required",
            regex: "Maximum 1000 characters allowed",
        },
        search_terms: {
            required: "Search terms are required",
            regex: "Contains invalid characters",
        },
        advertising_keywords: {
            required: "Advertising keywords are required",
            regex: "Contains invalid characters",
        },
        product_category: {
            required: "Product category is required",
            regex: "Contains invalid characters",
        },
        item_price: {
            number: "Please enter a valid number",
            min: "Minimum value is 0",
            regex: "Please enter a valid price (up to 2 decimals)",
        },
        postage: {
            number: "Please enter a valid number",
            min: "Minimum value is 0",
            regex: "Please enter a valid price (up to 2 decimals)",
        },
        base_price: {
            number: "Please enter a valid number",
            min: "Minimum value is 0",
            regex: "Please enter a valid price (up to 2 decimals)",
        },
        fba_fee: {
            number: "Please enter a valid number",
            min: "Minimum value is 0",
            regex: "Please enter a valid price (up to 2 decimals)",
        },
        duty: {
            number: "Please enter a valid number",
            min: "Minimum value is 0",
            regex: "Please enter a valid price (up to 2 decimals)",
        },
        air_ship: {
            number: "Please enter a valid number",
            min: "Minimum value is 0",
            regex: "Please enter a valid price (up to 2 decimals)",
        },
        commercial_invoice_title: {
            regex: "Contains invalid characters",
        },
        hs_code: {
            regex: "HS Code must be exactly 6 digits",
        },
        hs_code_percentage: {
            number: "Please enter a valid number",
            min: "Minimum value is 0",
            regex: "Please enter a valid percentage (up to 2 decimals)",
        },
        // item_size_cm: {
        //     regex: "Please enter a valid size (up to 2 decimals)",
        // },
        // ctn_size_cm: {
        //     regex: "Please enter a valid size (up to 2 decimals)",
        // },

        item_size_length_cm: {
            regex: "Please enter a valid length (up to 2 decimals)",
        },
        item_size_width_cm: {
            regex: "Please enter a valid width (up to 2 decimals)",
        },
        item_size_height_cm: {
            regex: "Please enter a valid height (up to 2 decimals)",
        },
        ctn_size_length_cm: {
            regex: "Please enter a valid length (up to 2 decimals)",
        },
        ctn_size_width_cm: {
            regex: "Please enter a valid width (up to 2 decimals)",
        },
        ctn_size_height_cm: {
            regex: "Please enter a valid height (up to 2 decimals)",
        },
        item_weight_kg: {
            number: "Please enter a valid number",
            min: "Minimum value is 0",
            regex: "Please enter a valid weight (up to 3 decimals)",
        },
        carton_weight_kg: {
            number: "Please enter a valid number",
            min: "Minimum value is 0",
            regex: "Please enter a valid weight (up to 3 decimals)",
        },
        quantity_per_carton: {
            number: "Please enter a valid integer",
            min: "Minimum value is 0",
            regex: "Must be an integer",
        },
        carton_cbm: {
            number: "Please enter a valid number",
            min: "Minimum value is 0",
            regex: "Please enter a valid volume (up to 4 decimals)",
        },
        moq: {
            number: "Please enter a valid integer",
            min: "Minimum value is 0",
            regex: "Must be an integer",
        },
        product_material: {
            regex: "Contains invalid characters",
        },
        order_lead_time_weeks: {
            number: "Please enter a valid integer",
            min: "Minimum value is 0",
            regex: "Must be an integer",
        },
        warnings: {
            regex: "Maximum 500 characters allowed",
        },
        listing_to_copy: {
            regex: "Must be numeric",
        },
        seasonal_type: {
            regex: "Contains invalid characters",
        },
        country: {
            regex: "Contains invalid characters",
        },
    },
    errorElement: "span",
    errorPlacement: function (error, element) {
        error.addClass("invalid-feedback");
        element.closest(".form-group").append(error);
    },
    highlight: function (element) {
        $(element).addClass("is-invalid");
    },
    unhighlight: function (element) {
        $(element).removeClass("is-invalid");
    },
});

$("#userAddForm").validate({
    rules: {
        email: {
            required: true,
            email: true,
        },
        name: {
            required: true,
            minlength: 3,
            regex: "^(?! )[a-zA-Z ]*[a-zA-Z]$",
        },
        role: {
            required: true, // Dropdown, just required
        },
    },
    messages: {
        email: {
            required: "Please enter your email",
            email: "Please enter a valid email address",
        },
        name: {
            required: "Please enter your name",
            minlength: "Name must be at least 3 characters",
            regex: "Name must not start with a space and end with only letters",
        },
        role: {
            required: "Please select a role",
        },
    },
    errorElement: "div",
    errorPlacement: function (error, element) {
        element.closest(".mb-3").append(error);
    },
    highlight: function (element) {
        $(element).addClass("error fw-bold");
    },
    unhighlight: function (element) {
        $(element).removeClass("error fw-bold");
    },
});

$("#profileForm").validate({
    rules: {
        name: {
            required: true,
            minlength: 3,
            regex: "^(?! )[a-zA-Z ]*[a-zA-Z]$",
        },
        email: {
            required: true,
            email: true,
        },
        mobile: {
            required: true,
            digits: true,
            minlength: 10,
            maxlength: 15,
        },
        profile_image: {
            extension: "jpg|jpeg|png|gif|bmp",
        },
    },
    messages: {
        name: {
            required: "Please enter your name",
            minlength: "Name must be at least 3 characters",
            regex: "Name cannot start with a space and must end with a letter",
        },
        email: {
            required: "Please enter your email",
            email: "Please enter a valid email address",
        },
        mobile: {
            required: "Please enter your mobile number",
            digits: "Mobile must contain only numbers",
            minlength: "Mobile must be at least 10 digits",
            maxlength: "Mobile cannot exceed 15 digits",
        },
        profile_image: {
            extension:
                "Only image files are allowed (jpg, jpeg, png, gif, bmp)",
        },
    },
    errorElement: "div",
    errorPlacement: function (error, element) {
        element.closest(".mb-3").append(error);
    },
    highlight: function (element) {
        $(element).addClass("is-invalid");
    },
    unhighlight: function (element) {
        $(element).removeClass("is-invalid");
    },
});

// Password Update Form Validation
$("#ChangePasswordForm").validate({
    rules: {
        current_password: {
            required: true,
            minlength: 6,
        },
        password: {
            required: true,
            minlength: 6,
        },
        password_confirmation: {
            required: true,
            equalTo: "#new-password",
        },
    },
    messages: {
        current_password: {
            required: "Please enter your current password",
            minlength: "Password must be at least 6 characters",
        },
        password: {
            required: "Please enter your new password",
            minlength: "Password must be at least 6 characters",
        },
        password_confirmation: {
            required: "Please confirm your new password",
            equalTo: "Passwords do not match",
        },
    },
    errorElement: "div",
    errorPlacement: function (error, element) {
        element.closest(".mb-3").append(error);
    },
    highlight: function (element) {
        $(element).addClass("is-invalid");
    },
    unhighlight: function (element) {
        $(element).removeClass("is-invalid");
    },
});

$("#addSourcingForm").validate({
    rules: {
        container_id: {
            required: true,
            minlength: 3,
        },
        descriptions: {
            maxlength: 500,
        },
    },
    messages: {
        container_id: {
            required: "Please enter the list name",
            minlength: "List name must be at least 3 characters",
        },
        descriptions: {
            maxlength: "Notes cannot exceed 500 characters",
        },
    },
    errorElement: "div",
    errorClass: "invalid-feedback",
    highlight: function (element) {
        $(element).addClass("is-invalid");
    },
    unhighlight: function (element) {
        $(element).removeClass("is-invalid");
    },
    errorPlacement: function (error, element) {
        error.insertAfter(element);
    },
    submitHandler: function (form) {
        if (confirm("Are you sure you want to submit?")) {
            form.submit();
        }
    },
});

$("#addContainerItemForm").validate({
    rules: {
        amazon_url: {
            required: true,
            url: true,
        },
        amz_price: {
            number: true,
            min: 0,
        },
    },
    messages: {
        amazon_url: {
            required: "Amazon URL is required.",
            url: "Please enter a valid URL.",
        },
        amz_price: {
            number: "Please enter a valid number for price.",
            min: "Price cannot be negative.",
        },
    },
    errorElement: "div",
    errorClass: "invalid-feedback",
    highlight: function (element) {
        $(element).addClass("is-invalid");
    },
    unhighlight: function (element) {
        $(element).removeClass("is-invalid");
    },
    errorPlacement: function (error, element) {
        error.insertAfter(element);
    },
    submitHandler: function (form) {
        form.submit();
    },
});

$("#addProductForm").validate({
    rules: {
        sku: {
            required: true,
        },
        short_title: {
            required: true,
            minlength: 3,
        },
        translator: {
            required: false,
        },
    },
    messages: {
        sku: {
            required: "Please enter the SKU",
        },
        short_title: {
            required: "Please enter a short title",
            minlength: "Short title must be at least 3 characters",
        },
        translator: {
            // required: "Please enter translator name"
        },
    },
    errorElement: "div",
    errorClass: "invalid-feedback",
    highlight: function (element) {
        $(element).addClass("error");
    },
    unhighlight: function (element) {
        $(element).removeClass("error");
    },
    errorPlacement: function (error, element) {
        error.insertAfter(element);
    },
    submitHandler: function (form) {
        if (confirm("Are you sure you want to submit?")) {
            form.submit();
        }
    },
});

$("#addWarehouseForm").validate({
    rules: {
        warehouse_name: {
            required: true,
            regex: '^[A-Za-z]+(?: +[A-Za-z]+)*$',
        },
        location: {
            required: true,
        },
    },
    messages: {
        warehouse_name: {
            required: "Please enter the warehouse name",
            regex: "Warehouse name must start and end with a letter, contain only letters and single spaces between words, and no numbers or special characters are allowed.",
        },
        location: {
            required: "Select an Country",
        },
    },
    errorElement: "div",
    errorClass: "invalid-feedback",
    highlight: function (element) {
        $(element).addClass("is-invalid");
    },
    unhighlight: function (element) {
        $(element).removeClass("is-invalid");
    },
    errorPlacement: function (error, element) {
        error.insertAfter(element);
    },
    submitHandler: function (form) {
        if (confirm("Are you sure you want to submit?")) {
            form.submit();
        }
    },
});


$("#purchaseOrderForm").validate({
    rules: {
        order_number: {
            required: true,
            maxlength: 100,
            regex: "^[A-Z0-9-]+$"
        },
        supplier_id: {
            required: true
        },
        warehouse_id: {
            required: true
        },
        order_date: {
            required: true,
            date: true
        },
        expected_arrival: {
            required: true,
            date: true
        },
        payment_terms: {
            required: true
        },
        shipping_method: {
            required: true,
            maxlength: 100,
            regex: "^[A-Za-z]+$"
        },
        status: {
            required: true
        },

    },
    messages: {
        order_number: {
            required: "Order number is required",
            maxlength: "Max 100 characters allowed",
            regex: "Use uppercase letters, numbers, and hyphens only"
        },
        supplier_id: {
            required: "Supplier is required"
        },
        warehouse_id: {
            required: "Warehouse is required"
        },
        order_date: {
            required: "Order date is required",
            date: "Enter a valid date"
        },
        expected_arrival: {
            required: "Expected arrival date is required",
            date: "Enter a valid date"
        },
        payment_terms: {
            required: "Payment terms are required"
        },
        shipping_method: {
            required: "Shipping method is required",
            maxlength: "Max 100 characters allowed",
            regex: "Only alphabets allowed"
        },
        status: {
            required: "Status is required"
        },

    },
    errorClass: "is-invalid",
    validClass: "is-valid",
    errorElement: "div",
    errorPlacement: function (error, element) {
        error.addClass("invalid-feedback");
        if (element.parent('.input-group').length) {
            error.insertAfter(element.parent());
        } else {
            error.insertAfter(element);
        }
    },
    highlight: function (element) {
        $(element).addClass("is-invalid").removeClass("is-valid");
    },
    unhighlight: function (element) {
        $(element).removeClass("is-invalid").addClass("is-valid");
    }
});

$("#forcastForm").validate({
    rules: {
        order_name: {
            required: true,
            maxlength: 100,
            regex: /^[A-Za-z0-9]+(?: [A-Za-z0-9]+)*$/  // no leading/trailing spaces, no special chars
        },
        order_date: {
            required: true,
            date: true
        },
        // status: {
        //     required: true
        // }
    },
    messages: {
        order_name: {
            required: "Forecast name is required",
            maxlength: "Max 100 characters allowed",
            regex: "Only letters, numbers and single spaces allowed. No special characters or leading/trailing spaces."
        },
        order_date: {
            required: "Forecast date is required",
            date: "Enter a valid date"
        },
        // status: {
        //     required: "Status is required"
        // }
    },
    errorClass: "is-invalid",
    validClass: "is-valid",
    errorElement: "div",
    errorPlacement: function (error, element) {
        error.addClass("invalid-feedback");
        if (element.parent('.input-group').length) {
            error.insertAfter(element.parent());
        } else {
            error.insertAfter(element);
        }
    },
    highlight: function (element) {
        $(element).addClass("is-invalid");
    },
    unhighlight: function (element) {
        $(element).removeClass("is-invalid");
    }
});

$("#keywordRuleForm").validate({
    rules: {
        ctr_condition: { number: true, min: 0 },
        conversion_condition: { number: true, min: 0 },
        acos_condition: { number: true, min: 0 },
        click_condition: { number: true, min: 0 },
        sales_condition: { number: true, min: 0 },
        impressions_condition: { number: true, min: 0 },
        action_label: {
            required: true,
            maxlength: 255
        },
        bid_adjustment: {
            required: true,
            maxlength: 255,
            bidPercentageOrPause: true
        },
        is_active: { required: true }
    },
    messages: {
        ctr_condition: { number: "Enter a valid number" },
        conversion_condition: { number: "Enter a valid number" },
        acos_condition: { number: "Enter a valid number" },
        click_condition: { number: "Enter a valid number" },
        sales_condition: { number: "Enter a valid number" },
        impressions_condition: { number: "Enter a valid number" },
        action_label: {
            required: "Recommendation text is required",
            maxlength: "Max 255 characters allowed"
        },
        bid_adjustment: {
            required: "Bid adjustment is required",
            maxlength: "Max 255 characters allowed",
            bidWholeOrPause: "Enter a whole number (e.g., 10) or check ❌ Pause"
        },
        is_active: { required: "Status is required" }
    },
    errorClass: "is-invalid",
    validClass: "is-valid",
    errorElement: "div",
    errorPlacement: function (error, element) {
        error.addClass("invalid-feedback");
        error.insertAfter(element);
    },
    highlight: function (element) {
        $(element).addClass("is-invalid");
    },
    unhighlight: function (element) {
        $(element).removeClass("is-invalid");
    },

    // 🔒 disable when valid form is submitting
    submitHandler: function (form) {
        const $btn = $(form).find('button[type="submit"]');
        $btn.prop('disabled', true);
        form.submit();
    },

    // 🔁 re-enable dynamically when all fields valid again
    onkeyup: function (element) {
        this.element(element);
        toggleSubmitButton(this);
    },
    onfocusout: function (element) {
        this.element(element);
        toggleSubmitButton(this);
    },
    onchange: function (element) {
        this.element(element);
        toggleSubmitButton(this);
    }
});

// ✅ Helper function to toggle submit button
function toggleSubmitButton(validator) {
    const $form = $(validator.currentForm);
    const $btn = $form.find('button[type="submit"]');
    if ($form.valid()) {
        $btn.prop('disabled', false); // re-enable when valid
    } else {
        $btn.prop('disabled', true);
    }
}

// ✅ Custom rule: only whole numbers or "❌ Pause"
$.validator.addMethod("bidWholeOrPause", function (value, element) {
    const pauseChecked = $('#pauseCheckbox').is(':checked');

    // Allow pause
    if (pauseChecked) return true;

    // Only allow whole numbers (no decimals, no negative)
    return /^[0-9]+$/.test(value);
}, "Enter a whole number (e.g., 10) or check ❌ Pause");




