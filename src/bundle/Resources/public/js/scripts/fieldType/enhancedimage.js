/*
 * NovaeZEnhancedImageAssetBundle.
 *
 * @package   NovaeZEnhancedImageAssetBundle
 *
 * @author    Novactive <f.alexandre@novactive.com>
 * @copyright 2018 Novactive
 * @license   https://github.com/Novactive/NovaeZEnhancedImageAssetBundle/blob/master/LICENSE
 *
 */

(function (global) {
    const SELECTOR_FIELD = '.ez-field-edit--enhancedimage';
    const SELECTOR_INPUT_FILE = 'input[type="file"]';
    const SELECTOR_LABEL_WRAPPER = '.ez-field-edit__label-wrapper';
    const SELECTOR_ALT_WRAPPER = '.ez-field-edit-preview__image-alt';
    const SELECTOR_INPUT_ALT = '.ez-field-edit-preview__image-alt .ez-data-source__input';

    const SELECTOR_FRAME = '.focuspoint-helper--frame';
    const SELECTOR_IMG = '.focuspoint-helper--img';
    const SELECTOR_RETICULE = '.focuspoint-helper--reticle';
    const SELECTOR_INPUT_FOCUS_X = '.focuspoint-helper--input-focus-x';
    const SELECTOR_INPUT_FOCUS_Y = '.focuspoint-helper--input-focus-y';

    if (!global.eZ.BasePreviewField || !global.eZ.BaseFileFieldValidator) return null;

    class EnhancedImageFilePreviewField extends global.eZ.BasePreviewField {
        /**
         * Gets a temporary image URL
         *
         * @method getImageUrl
         * @param {File} file
         * @param {Function} callback the callback returns a retrieved file's temporary URL
         */
        getImageUrl(file, callback) {
            const reader = new FileReader();
            reader.onload = readerEvent => {
                const i = new Image();
                i.onload = imageEvent => callback(imageEvent.target);
                i.src = readerEvent.target.result;
            }
            reader.readAsDataURL(file);
        }

        /**
         * Loads dropped file preview.
         * It should redefined in each class that extends this one.
         *
         * @method loadDroppedFilePreview
         * @param {Event} event
         */
        loadDroppedFilePreview(event) {
            const preview = this.fieldContainer.querySelector('.ez-field-edit__preview');
            const images = preview.querySelectorAll('.ez-field-edit-preview__media');
            const nameContainer = preview.querySelector('.ez-field-edit-preview__file-name');
            const sizeContainer = preview.querySelector('.ez-field-edit-preview__file-size');
            const files = [].slice.call(event.target.files);
            const fileSize = this.formatFileSize(files[0].size);

            this.getImageUrl(files[0], img => {
                [...this.fieldContainer.querySelectorAll(SELECTOR_FRAME)].forEach(frame => {
                    const image = frame.querySelector('img');
                    image.setAttribute('src', img.src);
                    frame.setAttribute('data-image-w', img.width);
                    frame.setAttribute('data-image-h', img.height);
                    frame.dispatchEvent(new CustomEvent('focusChange'));
                });
                [...images].forEach(image => {
                    image.setAttribute('src', img.src);
                });
            });

            nameContainer.innerHTML = files[0].name;
            nameContainer.title = files[0].name;
            sizeContainer.innerHTML = fileSize;
            sizeContainer.title = fileSize;

            preview.querySelector('.ez-field-edit-preview__action--preview').href = URL.createObjectURL(files[0]);
            this.fieldContainer.querySelector(SELECTOR_INPUT_ALT).dispatchEvent(new CustomEvent('cancelErrors'));
        }
    }

    class EnhancedImageFieldValidator extends global.eZ.BaseFileFieldValidator {
        /**
         * Validates the alternative text input
         *
         * @method validateAltInput
         * @param {Event} event
         * @returns {Object}
         * @memberof EzStringValidator
         */
        validateAltInput(event) {
            const isRequired = event.target.required;
            const isEmpty = !event.target.value;
            const isError = (isEmpty && isRequired);
            const label = event.target.closest(SELECTOR_ALT_WRAPPER).querySelector('.ez-data-source__label').innerHTML;
            const result = {isError};

            if (isEmpty) {
                result.errorMessage = global.eZ.errors.emptyField.replace('{fieldName}', label);
            }

            return result;
        }

        focus(event) {
            const img = event.target,
                imageW = img.width,
                imageH = img.height;

            //Calculate FocusPoint coordinates
            const focusX = (event.offsetX / imageW - .5) * 2,
                focusY = (event.offsetY / imageH - .5) * -2;

            this.fieldContainer.querySelector(SELECTOR_INPUT_FOCUS_X).setAttribute('value', focusX.toFixed(2));
            this.fieldContainer.querySelector(SELECTOR_INPUT_FOCUS_Y).setAttribute('value', focusY.toFixed(2));

            this.updateReticulePosition();
        }

        updateReticulePosition() {
            //Calculate CSS Percentages
            let focusX = parseFloat(this.fieldContainer.querySelector(SELECTOR_INPUT_FOCUS_X).getAttribute('value')),
                focusY = parseFloat(this.fieldContainer.querySelector(SELECTOR_INPUT_FOCUS_Y).getAttribute('value'));

            [...this.fieldContainer.querySelectorAll(SELECTOR_FRAME)].forEach(frame => {
                frame.setAttribute('data-focus-x', focusX.toFixed(2));
                frame.setAttribute('data-focus-y', focusY.toFixed(2));
                frame.dispatchEvent(new CustomEvent('focusChange', {focusX: focusX, focusY: focusY}));
            });

            const percentageX = ((focusX + 1) / 2) * 100,
                percentageY = ((-focusY + 1) / 2) * 100;

            //Leave a sweet target reticle at the focus point.
            this.fieldContainer.querySelector(SELECTOR_RETICULE).removeAttribute('hidden');
            this.fieldContainer.querySelector(SELECTOR_RETICULE).style.top = `${percentageY.toFixed(0)}%`;
            this.fieldContainer.querySelector(SELECTOR_RETICULE).style.left = `${percentageX.toFixed(0)}%`;
        }
    }


    [...document.querySelectorAll(SELECTOR_FIELD)].forEach(fieldContainer => {
        const validator = new EnhancedImageFieldValidator({
            classInvalid: 'is-invalid',
            fieldContainer,
            eventsMap: [
                {
                    selector: `${SELECTOR_IMG}`,
                    eventName: 'click',
                    callback: 'focus',
                    errorNodeSelectors: [SELECTOR_LABEL_WRAPPER],
                    isValueValidator: false,
                },
                {
                    selector: `${SELECTOR_INPUT_FILE}`,
                    eventName: 'change',
                    callback: 'validateInput',
                    errorNodeSelectors: [SELECTOR_LABEL_WRAPPER],
                },
                {
                    selector: SELECTOR_INPUT_ALT,
                    eventName: 'blur',
                    callback: 'validateAltInput',
                    invalidStateSelectors: ['.ez-data-source__field--alternativeText'],
                    errorNodeSelectors: [`${SELECTOR_ALT_WRAPPER} .ez-data-source__label-wrapper`],
                },
                {
                    isValueValidator: false,
                    selector: `${SELECTOR_INPUT_FILE}`,
                    eventName: 'invalidFileSize',
                    callback: 'showFileSizeError',
                    errorNodeSelectors: [SELECTOR_LABEL_WRAPPER],
                },
                {
                    isValueValidator: false,
                    selector: SELECTOR_INPUT_ALT,
                    eventName: 'cancelErrors',
                    callback: 'cancelErrors',
                    invalidStateSelectors: ['.ez-data-source__field--alternativeText'],
                    errorNodeSelectors: [`${SELECTOR_ALT_WRAPPER} .ez-data-source__label-wrapper`],
                }
            ],
        });
        validator.updateReticulePosition();
        const previewField = new EnhancedImageFilePreviewField({
            validator,
            fieldContainer,
            fileTypeAccept: fieldContainer.querySelector(SELECTOR_INPUT_FILE).accept
        });

        previewField.init();

        global.eZ.fieldTypeValidators = global.eZ.fieldTypeValidators ?
            [...global.eZ.fieldTypeValidators, validator] :
            [validator];
    })
})(window);