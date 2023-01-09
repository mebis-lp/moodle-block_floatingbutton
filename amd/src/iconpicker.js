import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import ICON_SET from 'block_floatingbutton/iconset';

var iconpickermodal;

export const init = (iconpickerdiv, iconpickerselector) => {
    // Add index to icon set to make producing an unique id easier.
    ICON_SET.forEach(function(v, i) {
        v.index = i;
    });

    var clickSet = false;

    // Make inputs of the moodle form invisible and add button for iconpicker.
    let inputs = Array.from(document.querySelectorAll('.mbs-floatingicon-input input'));
    inputs.forEach(function(input) {
        input.setAttribute('style', 'visibility: collapse; width: 0; margin: 0; padding: 0; position: absolute;');
        input.insertAdjacentHTML(
            'afterend',
            '<button class="mbs-floatingicons-iconpicker btn btn-secondary btn-icon" type="button" id="' + input.id +
            '_button" data-icon-input="' + input.id + '"><i class="' + input.value + '"></i></button>'
        );
    });

    // Attach click listener to each iconpicker buton. The callback function also sets data-iconpicker
    // and data-icon-input attributes.
    let iconpickers = Array.from(document.querySelectorAll(iconpickerselector));
    iconpickers.forEach(function(picker) {
        picker.addEventListener('click', function() {
            iconpickermodal.show();
            document.querySelector('.mbs-iconpicker').setAttribute('data-iconpicker', picker.id);
            document.querySelector('.mbs-iconpicker').setAttribute('data-icon-input', picker.getAttribute('data-icon-input'));
            highlightselected();
        });
    });

    // Build iconpicker modal with moodle modal factory
    Templates.renderForPromise('block_floatingbutton/iconpicker', {icons: ICON_SET})
        .then(({html}) => {
            require(['jquery', 'core/modal_factory'], function($, ModalFactory) {
                var trigger = $('#create-modal');
                ModalFactory.create({
                    title: 'Icon picker',
                    body: html,
                    footer: '',
                }, trigger)
                    .done(function(modal) {
                        iconpickermodal = modal;
                        modal.getRoot().on('modal:shown', function() {
                            // Listeners for the icons and the search input have to be registered when modal is shown for the first
                            // time because modal doesn't exist in the DOM before.
                            if (!clickSet) {
                                let search = document.querySelector('#mbs-iconpicker-search');
                                search.addEventListener('input', searchicon);
                                let icons = Array.from(document.querySelectorAll('.mbs-iconpicker-icon'));
                                icons.forEach(function(icon) {
                                    icon.addEventListener('click', iconclick);
                                });
                                clickSet = true;
                            }
                        });
                    });
            });
            return true;
        }).catch(ex => displayException(ex));
};

/**
 * Show only icons that match the search value
 */
function searchicon() {
    let search = document.querySelector('#mbs-iconpicker-search');
    let icons = Array.from(document.querySelectorAll('.mbs-iconpicker-icon'));
    icons.forEach(function(icon) {
        if (
            icon.getAttribute('data-search').includes(search.value)
        ) {
            icon.setAttribute('style', '');
        } else {
            icon.setAttribute('style', 'display: none;');
        }
    });
}

/**
 * Adds class "highlight" to currently selected icon.
 */
function highlightselected() {
    let icons = Array.from(document.getElementsByClassName('mbs-iconpicker-icon'));
    let input = document.querySelector('.mbs-iconpicker').getAttribute('data-icon-input');
    if (!(input === null)) {
        let iconclass = document.getElementById(input).getAttribute('value');
        icons.forEach(function(icondiv) {
            icondiv.classList.remove('highlight');
            if (icondiv.querySelector('i').classList == iconclass) {
                icondiv.classList.add('highlight');
            }
        });
    }
}

/**
 * Function called when user clicks on an icon
 * @param {*} event
 */
function iconclick(event) {
    let id = document.querySelector('.mbs-iconpicker').getAttribute('data-iconpicker');
    let input = document.querySelector('.mbs-iconpicker').getAttribute('data-icon-input');
    if (id) {
        let icondiv = event.target;
        if (event.target.nodeName != 'DIV') {
            icondiv = event.target.parentNode;
        }
        let iconclass = document.querySelector('#' + icondiv.id + ' i').classList;
        document.getElementById(id).innerHTML = '<i class="' + iconclass + '"></i>';
        document.getElementById(input).setAttribute('value', iconclass);
    }
    highlightselected();
    iconpickermodal.hide();
}
