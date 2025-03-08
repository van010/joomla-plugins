
// default languages
var listLanguages = [
    {label: 'English', class: '', cEvent: 'lang_english', customData: ''},
    {label: 'Spanish', class: '', cEvent: 'lang_spanish', customData: ''},
    {label: 'Portuguese', class: '', cEvent: 'lang_portuguese', customData: ''},
    {label: 'Mandarin Chinese', class: '', cEvent: 'lang_m_chinese', customData: ''},
    {label: 'Hindi', class: '', cEvent: 'lang_hindi', customData: ''},
    {label: 'Arabic', class: '', cEvent: 'lang_arabic', customData: ''},
    {label: 'Bengali', class: '', cEvent: 'lang_bengali', customData: ''},
    {label: 'Russian', class: '', cEvent: 'lang_russian', customData: ''},
    {label: 'French', class: '', cEvent: 'lang_french', customData: ''},
    {label: 'Italian', class: '', cEvent: 'lang_italian', customData: ''},
    {label: 'German', class: '', cEvent: 'lang_german', customData: ''},
    {label: 'Dutch', class: '', cEvent: 'lang_dutch', customData: ''},
    {label: 'Korean', class: '', cEvent: 'lang_korean', customData: ''},
    {label: 'Japanese', class: '', cEvent: 'lang_japanese', customData: ''},
    {label: 'Vietnamese', class: '', cEvent: 'lang_vietnamese', customData: ''},
];

const handleListLangs = () => {
    if (!configs.langs.languages) return ;
    const mergeLangs = listLanguages.concat(configs.langs.languages);
    const uniqueLangs = mergeLangs.reduce((acc, curr) => {
        if (!acc.find(lang => lang.cEvent === curr.cEvent)){
            acc.push(curr);
        }
        return acc;
    }, []);
    listLanguages = uniqueLangs;
};

handleListLangs();

const langPromptCommands = () => {
    let prompts = {};
    listLanguages.forEach(function (el, idx){
        prompts[el.cEvent] = `translate into ${el.label} language based on: `;
    });
    return prompts;
}

const langPrompts = () => {
    const allLangs = {};
    listLanguages.forEach(function (lang, idx){
        const langCustom = {};
        langCustom[lang.cEvent] = lang;
        Object.assign(allLangs, langCustom);
    })
    return allLangs;
}

export {
    langPrompts,
    listLanguages,
    langPromptCommands
}