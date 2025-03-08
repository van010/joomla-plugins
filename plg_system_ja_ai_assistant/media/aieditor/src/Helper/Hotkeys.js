function addOpenChatHotKeys() {
    document.addEventListener('keydown', event => {
        if (event.key !== '/') {
            return;
        }

        console.log('object');
    })
}

export function addHotKeys() {
    addOpenChatHotKeys();
}