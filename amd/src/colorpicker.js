// Changes input type to "color" - spare for replacement with a polyfill.
export const init = () => {
    let inputs = Array.from(document.querySelectorAll('.mbs-floatingbutton-color-input input'));
    inputs.forEach(function(input) {
        input.setAttribute('type', 'color');
        if (input.getAttribute('value') == '') {
            input.removeAttribute('value');
        }
    });
};
