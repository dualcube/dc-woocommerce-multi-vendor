(function ($) {

    if (!wp || !wp.media || !frontend_dash_upload_script_params) {
        return;
    }
    media = wp.media;
    l10n = media.view.l10n = typeof _wpMediaViewsL10n === 'undefined' ? {} : _wpMediaViewsL10n;
    var wcmp_media_frame;
    var target;

    if (frontend_dash_upload_script_params.enableCrop) {
        /**
         * wp.media.view.Toolbar.Select
         *
         * @class
         * @augments wp.media.view.Toolbar.Select
         * @augments wp.media.view.Toolbar
         * @augments wp.media.View
         * @augments wp.Backbone.View
         * @augments Backbone.View
         */
        media.view.Toolbar.WCMpSelect = media.view.Toolbar.Select.extend({
            initialize: function () {
                var options = this.options;
                _.bindAll(this, 'clickSelect');

                _.defaults(options, {
                    event: 'select',
                    state: 'wcmp-cropper',
                    reset: true,
                    close: false,
                    text: l10n.select,

                    // Does the button rely on the selection?
                    requires: {
                        selection: true
                    }
                });

                options.items = _.defaults(options.items || {}, {
                    select: {
                        style: 'primary',
                        text: options.text,
                        priority: 80,
                        click: this.clickSelect,
                        requires: options.requires
                    }
                });
                // Call 'initialize' directly on the parent class.
                media.view.Toolbar.prototype.initialize.apply(this, arguments);
            }
        });


        /**
         * wp.media.view.Cropper
         *
         * Uses the imgAreaSelect plugin to allow a user to crop an image.
         *
         * Takes imgAreaSelect options from
         * wp.customize.HeaderControl.calculateImageSelectOptions via
         * wp.customize.HeaderControl.openMM.
         *
         * @class
         * @augments wp.media.View
         * @augments wp.Backbone.View
         * @augments Backbone.View
         */
        media.view.WCMpCropper = media.View.extend({
            className: 'crop-content',
            template: media.template('crop-content'),
            initialize: function () {
                _.bindAll(this, 'onImageLoad');
            },
            ready: function () {
                this.controller.frame.on('content:error:crop', this.onError, this);
                this.$image = this.$el.find('.crop-image');
                this.$image.on('load', this.onImageLoad);
                $(window).on('resize.cropper', _.debounce(this.onImageLoad, 250));
            },
            prepare: function () {
                return {
                    title: l10n.cropYourImage,
                    url: this.options.attachment.get('url')
                };
            },
            onImageLoad: function () {
                var imgOptions = this.controller.get('imgSelectOptions');
                if (typeof imgOptions === 'function') {
                    imgOptions = imgOptions(this.options.attachment, this.controller);
                }

                imgOptions = _.extend(imgOptions, {parent: this.$el});
                this.trigger('image-loaded');
                this.controller.imgSelect = this.$image.imgAreaSelect(imgOptions);
            },
            onError: function () {
                var filename = this.options.attachment.get('filename');

                this.views.add('.upload-errors', new media.view.UploaderStatusError({
                    filename: media.view.UploaderStatus.prototype.filename(filename),
                    message: _wpMediaViewsL10n.cropError
                }), {at: 0});
            }
        });


        /**
         * wp.media.controller.Cropper
         *
         * A state for cropping an image.
         *
         * @class
         * @augments wp.media.controller.State
         * @augments Backbone.Model
         */
        media.controller.WCMpCropper = media.controller.State.extend({
            defaults: {
                id: 'wcmp-cropper',
                title: l10n.cropImage,
                // Region mode defaults.
                toolbar: 'crop',
                content: 'crop',
                router: false,
                canSkipCrop: false
            },
            activate: function () {
                this.frame.on('content:create:crop', this.createCropContent, this);
                this.frame.on('close', this.removeCropper, this);
                this.set('selection', new Backbone.Collection(this.frame._selection.single));
            },

            deactivate: function () {
                this.frame.toolbar.mode('browse');
            },

            createCropContent: function () {
                this.cropperView = new wp.media.view.WCMpCropper({
                    controller: this,
                    attachment: this.get('selection').first()
                });
                this.cropperView.on('image-loaded', this.createCropToolbar, this);
                this.frame.content.set(this.cropperView);
            },
            removeCropper: function () {
                this.imgSelect.cancelSelection();
                this.imgSelect.setOptions({remove: true});
                this.imgSelect.update();
                this.cropperView.remove();
            },
            createCropToolbar: function () {
                var canSkipCrop, toolbarOptions;
                canSkipCrop = this.get('canSkipCrop') || false;
                toolbarOptions = {
                    controller: this.frame,
                    items: {
                        insert: {
                            style: 'primary',
                            text: l10n.cropImage,
                            priority: 80,
                            requires: {library: false, selection: false},

                            click: function () {
                                var self = this,selection;
                                selection = this.controller.state().get('selection').first();
                                selection.set({
                                    cropDetails: this.controller.state().imgSelect.getSelection(),
                                    cropOptions: this.controller.state().frame.options.cropOptions
                                });

                                this.$el.text(l10n.cropping);
                                this.$el.attr('disabled', true);
                                this.controller.state().doCrop(selection).done(function (croppedImage) {
                                    wcmpMediaCallback(croppedImage);
                                    self.controller.trigger('cropped', croppedImage);
                                    //self.controller.close();
                                    self.controller.setState('library');
                                    self.controller.toolbar.mode('select');
                                    self.controller.createSelection();
                                    self.controller.close();
                                    return false;
                                }).fail(function () {
                                    self.controller.trigger('content:error:crop');
                                });
                            }
                        }
                    }
                };

                if (canSkipCrop) {
                    _.extend(toolbarOptions.items, {
                        skip: {
                            style: 'secondary',
                            text: l10n.skipCropping,
                            priority: 70,
                            requires: {library: false, selection: false},
                            click: function () {
                                var selection = this.controller.state().get('selection').first();
                                wcmpMediaCallback(selection.attributes);
                                this.controller.state().cropperView.remove();
                                this.controller.trigger('skippedcrop', selection);
                                this.controller.setState('library');
                                this.controller.toolbar.mode('select');
                                this.controller.createSelection();
                                this.controller.close();
                                this.controller.close();
                            }
                        }
                    });
                }
                this.frame.toolbar.set(new wp.media.view.Toolbar(toolbarOptions));
            },

            doCrop: function (attachment) {
                return wp.ajax.post('wcmp_crop_image', {
                    nonce: attachment.get('nonces').edit,
                    id: attachment.get('id'),
                    cropDetails: attachment.get('cropDetails'),
                    cropOptions: attachment.get('cropOptions')
                });
            }
        });

        media.view.WCMpCropperFrame = media.view.MediaFrame.Select.extend({
            initialize: function () {
                if (!this.options.cropOptions) {
                    this.options.cropOptions = {};
                }
                this.options.cropOptions = _.defaults(this.options.cropOptions, {
                    maxWidth: 100,
                    maxHeight: 100
                });

                _.defaults(this.options, {
                    selection: [],
                    library: {},
                    multiple: false,
                    state: 'library',
                    content: 'library'
                });

                if (!this.options.croppedCallback) {
                    this.options.croppedCallback = $.noop
                }

                media.view.MediaFrame.prototype.initialize.apply(this, arguments);
                this.createSelection();
                this.createStates();
                this.bindHandlers();

                this.listenTo(this, 'cropped', this.handleCroppedImage);
            },
            reset: function () {
                this.states.invoke('trigger', 'reset');
                this.createSelection();
                return this;
            },
            createStates: function () {
                var options = this.options;
                this.states.add([
                    new media.controller.WCMpCropper({
                        canSkipCrop: frontend_dash_upload_script_params.canSkipCrop,
                        imgSelectOptions: this.calculateImageSelectOptions
                    })
                ]);

                this.states.add([
                    // Main states.
                    new media.controller.Library({
                        library: media.query(options.library),
                        multiple: options.multiple,
                        title: options.title,
                        menu: false,
                        priority: 20
                    })
                ]);
            },
            /**
             * Toolbars
             *
             * @param {Object} toolbar
             * @param {Object} [options={}]
             * @this wp.media.controller.Region
             */
            createSelectToolbar: function (toolbar, options) {
                options = options || this.options.button || {};
                options.controller = this;
                toolbar.view = new media.view.Toolbar.WCMpSelect(options);
            },
            calculateImageSelectOptions: function (attachment, controller) {
                var xInit = parseInt(controller.frame.options.cropOptions.maxWidth, 10),
                        yInit = parseInt(controller.frame.options.cropOptions.maxHeight, 10),
                        ratio, xImg, yImg, realHeight, realWidth,
                        imgSelectOptions;


                realWidth = attachment.get('width');
                realHeight = attachment.get('height');

                ratio = xInit / yInit;
                xImg = realWidth;
                yImg = realHeight;


                if (xImg / yImg > ratio) {
                    yInit = yImg;
                    xInit = yInit * ratio;
                } else {
                    xInit = xImg;
                    yInit = xInit / ratio;
                }

                imgSelectOptions = {
                    handles: 'corners',
                    aspectRatio: xInit + ':' + yInit,
                    keys: true,
                    instance: true,
                    persistent: true,
                    imageWidth: realWidth,
                    imageHeight: realHeight,
                    x1: 0,
                    y1: 0,
                    x2: xInit,
                    y2: yInit,
                    minWidth: controller.frame.options.cropOptions.maxWidth,
                    minHeight: controller.frame.options.cropOptions.maxHeight,
                    fadeSpeed: 1000
                };



                // @TODO max values to options

                return imgSelectOptions;
            },
            handleCroppedImage: function (image) {

                var model = new wp.media.model.Attachment(image);

                this.options.croppedCallback.call(this, model);
            }
        });

        function wcmpMediaCallback(attachment) {
            //console.log(attachment);
            //$('#vendor-cover-img').attr('src', attachment.url);
            try { 
                $('#' + target + '-img').attr('src', attachment.url);
                $('#' + target + '-img-url').attr('value', attachment.url);
                $('#' + target + '-img-id').val(attachment.id);
            } catch(err) {
                console.log( 'Error in wcmpMediaCallback: '+err )
            }
        }
        
        

        $('.wcmp_upload_btn').on('click', function (e) {
            e.preventDefault();
            target = $(this).data('target');
            // If the media frame already exists, reopen it.
//            if (wcmp_media_frame) {
//                wcmp_media_frame.open();
//                return;
//            }
            wcmp_media_frame = new media.view.WCMpCropperFrame({
                cropOptions: {
                    maxWidth: target == "vendor-cover" ? frontend_dash_upload_script_params.cover_ratio[0] : frontend_dash_upload_script_params.default_logo_ratio[0], //target width
                    maxHeight: target == "vendor-cover" ? frontend_dash_upload_script_params.cover_ratio[1] : frontend_dash_upload_script_params.default_logo_ratio[1] // target height
                },
                canSkipCrop: true,
                croppedCallback: wcmpMediaCallback //defaults to jquery.noop()
            });
            
            wcmp_media_frame.open();
        });
        
    } else {
        $('.wcmp_upload_btn').on('click', function (e) {
            e.preventDefault();
            target = $(this).data('target');
            // If the media frame already exists, reopen it.
            if (wcmp_media_frame) {
                wcmp_media_frame.open();
                return;
            }
            wcmp_media_frame = wp.media({
                multiple: false,
                library: {type: 'image'}
            });
            // When an image is selected in the media frame...
            wcmp_media_frame.on('select', function () {
                // Get media attachment details from the frame state
                var attachment = wcmp_media_frame.state().get('selection').first().toJSON();
//            if(target == "vendor-cover"){ 
//                $('#'+target+'-img').css('background-image', 'url(' + attachment.url + ')');
//            }else{ 
                $('#' + target + '-img').attr('src', attachment.url);
                //}
                $('#' + target + '-img-url').attr('value', attachment.url);
                $('#' + target + '-img-id').val(attachment.id);
            });
            // Finally, open the modal on click
            wcmp_media_frame.open();
        });
    }

})(jQuery);