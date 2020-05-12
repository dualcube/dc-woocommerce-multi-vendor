/* 
 * Initialization of angular app
 */
/* global vendor_registration_param */

var app = angular.module("vendor_registration", ['ui.sortable']);
/*
 * angular service to pass data from one controller to another
 */
app.service('vendor_registration_service', function () {
    var formJson = [];
    if(vendor_registration_param.form_data !== ''){
        angular.forEach(vendor_registration_param.form_data,function (data, key){
            vendor_registration_param.form_data[key].partial = data.partial.split('/')[(data.partial.split('/').length - 1)];
        });
        var formJson = vendor_registration_param.form_data;
    } 
    return {
        getField: function () {
            return formJson;
        },
        setField: function (value) {
            formJson = value;
        }
    };
});
/*
 * angular controller for field menu
 */
app.controller('postbox_menu',['$scope', 'vendor_registration_service', function ($scope, vendor_registration_service) {
    $scope.postboxClass = "";
    $scope.vendorStoreFieldClass = "";
    var formJson = vendor_registration_service.getField();
    $scope.addFormField = function (type, label, event) {
        event.preventDefault();
        var jsonLength = formJson.length;
        switch (type) {
            case 'selectbox':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    selecttype: 'radio',
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    required: false,
                    options: [
                        {
                            value: 'option1',
                            label: 'Option 1',
                            selected: false
                        },
                        {
                            value: 'option2',
                            label: 'Option 2',
                            selected: true
                        },
                        {
                            value: 'option3',
                            label: 'Option 3',
                            selected: false
                        }
                    ],
                    cssClass: ''
                });
                break;
            case 'email':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    placeholder: '',
                    required: false,
//                    emailValidation: false,
                    cssClass: ''
                });
                break;
            case 'textarea':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    defaultValue: '',
                    limit : '',
                    required: false,
                    cssClass: ''
                });
                break;
            case 'checkbox':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    defaultValue: 'unchecked',
                    required: false,
                    cssClass: ''
                });
                break;
            case 'recaptcha':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    recaptchatype: 'v3',
                    hidden: false,
                    partial: type + '.html',
                    script: '',
                    required: false
                });
                break;
            case 'file':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    fileSize: '',
                    fileType: [
                        {
                            value : 'application/pdf',
                            label : 'PDF',
                            selected : false
                        },
                        {
                            value : 'image/jpeg',
                            label : 'JPEG',
                            selected : false
                        },
                        {
                            value : 'image/png',
                            label : 'PNG',
                            selected : false
                        },
                        {
                            value : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            label : 'DOC',
                            selected : false
                        },
                        {
                            value : 'application/vnd.ms-excel',
                            label : 'xls',
                            selected : false
                        }
                    ],
                    required: false,
                    muliple: false,
                    cssClass: ''
                });
                break;
            case 'separator':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    cssClass: ''
                });
                break;
            case 'vendor_description':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    defaultValue: '',
                    limit : '',
                    required: false,
                    cssClass: ''
                });
                break;
            case 'vendor_address_1':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    placeholder: '',
                    required: false,
                    cssClass: ''
                });
                break;
            case 'vendor_address_2':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    placeholder: '',
                    required: false,
                    cssClass: ''
                });
                break;
            case 'vendor_phone':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    placeholder: '',
                    required: false,
                    cssClass: ''
                });
                break;
            case 'vendor_country':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    placeholder: '',
                    required: false,
                    cssClass: ''
                });
                break;
            case 'vendor_state':
                // add dependancies with vendor country
                if (jQuery.inArray('vendor_country', jQuery.map(formJson, function(v) { return v.type; })) > -1) {
                    formJson.push({
                        id: jsonLength,
                        type: type,
                        label: label,
                        hidden: false,
                        partial: type + '.html',
                        placeholder: '',
                        required: false,
                        cssClass: ''
                    });
                }else{ alert(vendor_registration_param.lang.need_country_dependancy);}
                break;
            case 'vendor_city':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    placeholder: '',
                    required: false,
                    cssClass: ''
                });
                break;
            case 'vendor_postcode':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    placeholder: '',
                    required: false,
                    cssClass: ''
                });
                break;
            case 'vendor_paypal_email':
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    placeholder: '',
                    required: false,
                    cssClass: ''
                });
                break;
            default :
                formJson.push({
                    id: jsonLength,
                    type: type,
                    label: label,
                    hidden: false,
                    partial: type + '.html',
                    placeholder: '',
                    required: false,
                    cssClass: ''
                });
                break;
        }

        vendor_registration_service.setField(formJson);
    };
    $scope.togglePostbox = function () {
        if ($scope.postboxClass === "") {
            $scope.postboxClass = "closed";
        } else {
            $scope.postboxClass = "";
        }
    };
    $scope.togglevendorStoreField = function () {
        if ($scope.vendorStoreFieldClass === "") {
            $scope.vendorStoreFieldClass = "closed";
        } else {
            $scope.vendorStoreFieldClass = "";
        }
    };
}]);
/*
 * angular controller for form fields
 */
app.controller('postbox_content',['$scope', '$http', 'vendor_registration_service', function ($scope, $http, vendor_registration_service) {
    var formJson = vendor_registration_service.getField();
    $scope.fields = formJson;
    $scope.partialUrl = vendor_registration_param.partials;
    $scope.showSaveSpinner = false;
    $scope.togglePostboxField = function (index) {
        if ($scope.fields[index].hidden) {
            $scope.fields[index].hidden = false;
        } else {
            $scope.fields[index].hidden = true;
        }
    };
    $scope.removeFormField = function (index, event) {
        event.preventDefault();
        formJson.splice(index, 1);
        vendor_registration_service.setField(formJson);
    };
    $scope.addSelectBoxOption = function (index, event) {
        event.preventDefault();
        var count = $scope.fields[index].options.length + 1;
        $scope.fields[index].options.push({value: 'option' + count, label: 'Option ' + count, selected: false});
    };
    $scope.removeSelectboxOption = function (index, key, event) {
        event.preventDefault();
        $scope.fields[index].options.splice(key, 1);
    };
    $scope.fieldSortableOptions = {
        stop: function (e, ui) {

        }
    };
    $scope.listOnchange = function (parentIndex,index){
        console.log($scope.fields[parentIndex].options);
        angular.forEach($scope.fields[parentIndex].options,function(value,key){
            if(key !== index){
                $scope.fields[parentIndex].options[key].selected = false;
            }
        });
    };
    $scope.saveFormData = function () {
        $scope.showSaveSpinner = true;
        
        var data = jQuery.param({
            action: 'wcmp_save_vendor_registration_form',
            form_data: JSON.stringify($scope.fields)
        });
        //console.log(data);
        var config = {
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };
        $http.post(vendor_registration_param.ajax_url,data,config).success(function (data, status, headers, config){
            $scope.showSaveSpinner = false;
        }).error(function (data, status, header, config){
            console.log(data);
            $scope.showSaveSpinner = false;
        });
    };
}]);

