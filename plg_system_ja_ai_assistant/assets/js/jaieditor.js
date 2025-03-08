
document.addEventListener('DOMContentLoaded', function (){
	// const tinyIframe = document.getElementById('jform_articletext_ifr');
    return ;
    const checkEditArea = setInterval(() => {
        const editArea = document.querySelector('div.tox-edit-area');
        const tinyIframe = document.getElementById('jform_articletext_ifr');
        if (tinyIframe) {
            const iframeDocument = tinyIframe.contentDocument || tinyIframe.contentWindow.document;
            iframeDocument.addEventListener('selectionchange', function () {
                const selection = iframeDocument.getSelection().toString().trim();
                if (selection !== '') {
                    console.log('Selected text:', selection);
                    // You can perform further actions here based on the selected text
                }
            });
        }
    }, 200);
    setTimeout(() => {
        clearInterval(checkEditArea);
    }, 500);
})
