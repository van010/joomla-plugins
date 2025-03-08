import { selectedText } from "@/Helper/Store";
import {countWords, splitPrompts} from "@/Helper/Utilities";

// default tones
var listTones = [
    {label: 'Formal', class: '', cEvent: 'change_tone_formal', customData: ''},
    {label: 'Informal', class: '', cEvent: 'change_tone_informal', customData: ''},
    {label: 'Casual', class: '', cEvent: 'change_tone_casual', customData: ''},
    {label: 'Professional', class: '', cEvent: 'change_tone_professional', customData: ''},
    {label: 'Serious', class: '', cEvent: 'change_tone_serious', customData: ''},
    {label: 'Persuasive', class: '', cEvent: 'change_tone_persuasive', customData: ''},
    {label: 'Authoritative', class: '', cEvent: 'change_tone_authoritative', customData: ''},
    {label: 'Straightforward', class: '', cEvent: 'change_tone_straightforward', customData: ''},
    {label: 'Confident', class: '', cEvent: 'change_tone_confident', customData: ''},
    {label: 'Friendly', class: '', cEvent: 'change_tone_friendly', customData: ''},
    {label: 'Humorous', class: '', cEvent: 'change_tone_humorous', customData: ''},
    {label: 'Playful', class: '', cEvent: 'change_tone_playful', customData: ''},
    {label: 'Academic', class: '', cEvent: 'change_tone_academic', customData: ''},
    {label: 'Inspirational', class: '', cEvent: 'change_tone_inspirational', customData: ''},
];

const handleListTones = () => {
    if (!configs.tones.tones) return ;
    const mergeTones = listTones.concat(configs.tones.tones);
    const uniqueTones = mergeTones.reduce((acc, curr) => {
        if (!acc.find(tone => tone.cEvent === curr.cEvent)){
            acc.push(curr);
        }
        return acc;
    }, []);
    listTones = uniqueTones;
}

handleListTones();

const tonePromptCommands = () => {
    let prompts = {};
    const words = countWords(selectedText.value);
    const subPrompts = Object.assign({}, splitPrompts(), {limit_around_words_tone: `around ${words} words`});

    listTones.forEach(function (el, idx){
        const [first, second, third] = el.cEvent.split('_');
        prompts[el.cEvent] = [first, second].join(' ') + ' to a ' + third + ' one' + `, ${subPrompts.keep_origin_lang}, ${subPrompts.limit_around_words_tone}`;
    });
    return prompts;
};

const tonePrompts = () => {
    const allTones = {};
    listTones.forEach(function (tone, idx){
        const toneCustom = {};
        toneCustom[tone.cEvent] = tone;
        Object.assign(allTones, toneCustom);
    })
    return allTones;
};

export {
    listTones,
    tonePrompts,
    tonePromptCommands
}