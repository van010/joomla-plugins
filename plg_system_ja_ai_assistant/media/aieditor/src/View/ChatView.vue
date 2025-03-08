<template>
    <n-spin class="chat-spinner" :show="spinShow">
        <div class="chat-wrapper">
            <div class="messages-wrapper" ref="messageWrapper">
                <div class="item-chat" v-for="item in messages" :key="item.key" :class="`item-${item.role}`"
                    @click="event => showContextMenu(event, item)">
                    <div class="item-text" v-if="item.type === 'text'">{{ item.content }}</div>
                    <div class="item-image" v-if="item.type === 'image'">
                        <img style="width: 200px; height: 200px;" :src="item.content">
                    </div>
                </div>
            </div>

            <n-dropdown placement="bottom-start" trigger="manual" :x="dropdownX" :y="dropdownY" :options="dropdownOptions"
                :show="showDropdown" :on-clickoutside="onClickoutside" @select="handleSelect" />
            <br>
            <div class="promt-box">
                <n-select v-model:value="aiTask" :options="options" :disabled="waiting" />
                <br>
                <n-input placeholder="Ctrl + Enter to ask AI" type="textarea" size="small" v-model:value="aiInputContent"
                    :autosize="{ minRows: 3, maxRows: 15 }" :disabled="waiting" @keydown="handleInputKeydown" />
            </div>

        </div>
    </n-spin>
</template>

<script setup>
import { NInput, NSelect, NDropdown, NSpin, useMessage } from 'naive-ui';
import { aiInputContent } from '@/Helper/Store';
import { nextTick, ref } from 'vue';
import { computed } from 'vue';
import { insertValueToEditor } from "@/Helper/Editor";

const baseUri = Joomla.getOptions('system.paths').base;
const endpoint = baseUri + '/index.php?option=com_ajax&plugin=jaaiassistant&format=json&group=system';
const aiTask = ref('askai');
const waiting = ref(false);
const messageWrapper = ref('');
const dropdownX = ref(0);
const dropdownY = ref(0);
const showDropdown = ref(false);
const selectedItem = ref({
    type: 'text',
    role: 'user',
});
const spinShow = ref(false)
const noti = useMessage();

const dropdownOptions = computed(() => {
    switch (selectedItem.value.type) {
        case 'text':
            return [
                {
                    label: 'Insert to editor',
                    key: 'insert_text_to_editor',
                    props: {
                        onClick: () => {
                            doInputTask('insert_text_to_editor', selectedItem.value)
                            noti.success('success');
                        }
                    }
                },
                {
                    label: 'Use it as title',
                    key: 'use_it_as_title',
                    props: {
                        onClick: () => {
                            doInputTask('use_it_as_title', selectedItem.value)
                            noti.success('success');
                        }
                    }
                },
                {
                    label: 'Use it as meta description',
                    key: 'use_it_as_meta_desc',
                    props: {
                        onClick: () => {
                            doInputTask('use_it_as_meta_desc', selectedItem.value)
                            noti.success('success');
                        }
                    }
                },
            ];

        case 'image':
            return [
                {
                    label: 'Use as intro image',
                    key: 'use_as_intro_image',
                    props: {
                        onClick: () => {
                            doInputTask('use_as_intro_image', selectedItem.value)
                        }
                    }
                },
                {
                    label: 'Use as full text image',
                    key: 'use_as_full_text_image',
                    props: {
                        onClick: () => {
                            doInputTask('use_as_full_text_image', selectedItem.value)
                        }
                    }
                },
                {
                    label: 'Insert Image to editor',
                    key: 'insert_image_to_editor',
                    props: {
                        onClick: () => {
                            doInputTask('insert_image_to_editor', selectedItem.value)
                        }
                    }
                },
            ];
    }

    return [];
})

const options = [
    {
        label: "Ask AI ...",
        value: "askai",
    },
    {
        label: "Continue Writing",
        value: "continue_writing"
    },
    {
        label: "Create an Image",
        value: "create_image"
    },
];

const messages = ref([
    {
        role: 'user',
        type: 'text',
        content: 'who is batman',
    },
    {
        role: 'assistant',
        type: 'text',
        content: `Batman is a fictional superhero appearing in American comic books published by DC Comics. The character was created by artist Bob Kane and writer Bill Finger, and first appeared in Detective Comics #27 in 1939. Batman's secret identity is Bruce Wayne, a wealthy American playboy, philanthropist, and owner of Wayne Enterprises based in Gotham City. 

Bruce Wayne becomes Batman after witnessing the murder of his parents, Dr. Thomas Wayne and Martha Wayne, as a child, and he swears to avenge their deaths by fighting crime in Gotham. Unlike most superheroes, Batman does not possess any superpowers; instead, he relies on his intellect, physical prowess, detective skills, and an assortment of technology and gadgets developed or acquired by his company Wayne Enterprises' tech division. Batman operates in the shadowy Gotham City, often depicted as dark and corrupt.

Bruce Wayne becomes Batman after witnessing the murder of his parents, Dr. Thomas Wayne and Martha Wayne, as a child, and he swears to avenge their deaths by fighting crime in Gotham. Unlike most superheroes, Batman does not possess any superpowers; instead, he relies on his intellect, physical prowess, detective skills, and an assortment of technology and gadgets developed or acquired by his company Wayne Enterprises' tech division. Batman operates in the shadowy Gotham City, often depicted as dark and corrupt.

Batman is a key member of the Justice League, an assembly of superheroes including Superman, Wonder Woman, Flash, Green Lantern, Aquaman, and others. Over the decades, the character has become an iconic figure across various media, including cartoons, TV series, movies, and video games, pushing beyond the boundaries of comic books.

Batman is a key member of the Justice League, an assembly of superheroes including Superman, Wonder Woman, Flash, Green Lantern, Aquaman, and others. Over the decades, the character has become an iconic figure across various media, including cartoons, TV series, movies, and video games, pushing beyond the boundaries of comic books.

Batman is a key member of the Justice League, an assembly of superheroes including Superman, Wonder Woman, Flash, Green Lantern, Aquaman, and others. Over the decades, the character has become an iconic figure across various media, including cartoons, TV series, movies, and video games, pushing beyond the boundaries of comic books.

The character's immense popularity has led to a vast array of storylines, a rich cast of secondary characters, such as Robin (his sidekick), the butler Alfred Pennyworth, Commissioner Gordon, and a notable roster of villains like the Joker, Catwoman, the Penguin, the Riddler, Two-Face, and Scarecrow. Batman's complex personality, moral ambiguity, and his drive for justice have made him a rich subject for study in the field of pop culture and the archetype of the dark hero.`,
    },
])

async function askAI() {
    const content = aiInputContent.value.trim();

    if (!content) {
        alert('ai prompt is empty');

        return;
    }

    if (!aiTask.value) {
        return;
    }

    const formData = new FormData();

    formData.append('aitask', aiTask.value);
    formData.append('content', aiInputContent.value);

    aiInputContent.value = '';

    messages.value.push({
        role: 'user',
        type: 'text',
        content: content,
    })

    nextTick(() => {
        waiting.value = true;
        spinShow.value = true;
        scrollChatToNewMessage();
    })

    try {
        const res = await fetch(endpoint, {
            method: 'post',
            body: formData,
        });

        const resData = await res.json();

        if (resData.success) {
            const aiData = resData.data.shift();

            messages.value.push({
                role: 'assistant',
                type: aiData.type,
                content: aiData.data,
            })
        }
    } catch (error) {
        console.log(error);
        alert('ai got error ~.~');
    }

    nextTick(() => {
        waiting.value = false;
        spinShow.value = false;

        scrollChatToNewMessage();
    })
}

function scrollChatToNewMessage() {
    const $wrapper = messageWrapper.value;
    const $last = $wrapper.lastElementChild;

    $wrapper.scrollTop = $last.offsetTop;
}

function handleInputKeydown(event) {
    if (event.ctrlKey && event.key === 'Enter') {
        askAI();
    }
}

function showContextMenu(event, item) {
    if (item.role !== 'assistant') {
        return;
    }

    selectedItem.value = item;

    dropdownX.value = event.clientX;
    dropdownY.value = event.clientY

    showDropdown.value = true;
}

function onClickoutside() {
    showDropdown.value = false;
}

function handleSelect() {
    showDropdown.value = false;
}

function updateTitleInput(content) {
    document.querySelector('#jform_title').value = content
}

function updateMetaDescInput(content) {
    document.querySelector('#jform_metadesc').value = content;
}

function buildImagePath(image) {
    const relativePath = image.path.replace('local-images:/', '');
    const imagePath = [
        'images/',
        relativePath,
        '#JoomlaImage://local-images/',
        relativePath,
        `?width=${image.width}&height=${image.height}`
    ].join('');

    return imagePath;
}

async function updateIntroImageInput(content) {
    spinShow.value = true;
    waiting.value = true;

    const image = await downloadImage(content);
    const imagePath = buildImagePath(image);

    const $input = document.querySelector('#imageModal_jform_images_image_intro');
    const $field = $input.parentElement;
    $field.setValue(imagePath);

    spinShow.value = false;
    waiting.value = false;

    noti.success('success');
}

async function updateFullTextImageInput(content) {
    spinShow.value = true;
    waiting.value = true;

    const image = await downloadImage(content);
    const imagePath = buildImagePath(image);

    const $input = document.querySelector('#imageModal_jform_images_image_fulltext');
    const $field = $input.parentElement;
    $field.setValue(imagePath);

    spinShow.value = false;
    waiting.value = false;

    noti.success('success');
}

async function insertImageToEditor(url) {
    spinShow.value = true;
    waiting.value = true;

    const image = await downloadImage(url);
    const imagePath = 'images' + image.path.replace('local-images:', '');
    const html = `<img src="${imagePath}" width="${image.width}" height="${image.height}" loading="lazy"/>`;

    insertValueToEditor(html);

    spinShow.value = false;
    waiting.value = false;

    noti.success('success');
}

function insertTextToEditor(content) {
    const frags = content.split('\n');
    const html = frags
        .filter(frag => frag.trim())
        .map(frag => {
            return `<p>${frag}</p>`;
        }).join('')

    insertValueToEditor(html);
}

async function downloadImage(url) {
    const formData = new FormData();

    formData.append('task', 'downloadImage');
    formData.append('url', encodeURIComponent(url));

    try {
        const res = await fetch(endpoint, {
            method: 'post',
            body: formData,
        });

        const resData = await res.json();

        if (resData.success) {
            return resData.data.shift();
        }
    } catch (error) {
        console.log(error);
        alert('ai got error ~.~');
    }
}

function doInputTask(task, data) {
    switch (task) {
        case 'insert_text_to_editor':
            insertTextToEditor(data.content)
            break;

        case 'use_it_as_title':
            updateTitleInput(data.content)
            break;

        case 'use_it_as_meta_desc':
            updateMetaDescInput(data.content)
            break

        case 'use_as_intro_image':
            updateIntroImageInput(data.content)
            break

        case 'use_as_full_text_image':
            updateFullTextImageInput(data.content)
            break

        case 'insert_image_to_editor':
            insertImageToEditor(data.content);
            break
    }
}
</script>

<style lang="scss">
.chat-spinner {
    width: 100%;
    height: 100%;

    .n-spin-content {
        position: relative;
        width: 100%;
        height: 100%;
    }
}

.chat-wrapper {
    display: flex;
    flex-direction: column;
    height: 100%;
    width: 100%;
}

.messages-wrapper {
    scroll-behavior: smooth;
    overflow-y: auto;
    flex: 1;
    position: relative;
}

.promt-box {
    flex-shrink: 0;
}

.item-chat {
    margin-top: 1rem;
    margin-bottom: 1rem;
    border: dashed 1px #ccc;
}

.item-text {
    white-space: pre-wrap;
}

.item-assistant {
    background-color: #dee2e6;
    cursor: pointer;
}
</style>