function bfsecurimagePlay() {

    let audio = document.getElementById('captcha_image_audio');

    if (audio.classList.contains('audioActive')) return false;

    audio.classList.add('audioActive');

    let img = document.getElementById("captcha_bfsecurimage_play_image")

    img.src = audio.dataset.loadingicon;

    audio.play();

    return false;
}

function bfsecurimageAudioEnded(el) {

    let img = document.getElementById("captcha_bfsecurimage_play_image")

    img.src = el.dataset.audioicon;

    el.classList.remove('audioActive');

    return false;
}

function bfsecurimageRefresh(el) {
    let div = document.getElementById('captcha_image_audio_div');

    div.innerHTML = '';

    let source = atob(div.dataset.source);

    let src = el.dataset.playsrc + '&' + Math.random();

    source = source.replace('%s', src);

    let audio = atob(div.dataset.audio);

    audio = audio.replace('%s', source);

    let img = document.getElementById('captcha_bfsecurimage_image');

    img.src = '';

    img.src = el.dataset.imgsrc + '&' + Math.random();

    div.innerHTML = audio;

    return false;
}
