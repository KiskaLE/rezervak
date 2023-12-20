document.addEventListener("DOMContentLoaded", function () {


    const spinner = new mojs.Shape({
        parent: '#loading-animation',
        shape: 'circle',
        stroke: '#FC46AD',
        strokeDasharray: '125, 125',
        strokeDashoffset: {'0': '-125'},
        strokeWidth: 5,
        fill: 'none',
        left: '50%',
        top: '50%',
        rotate: {'-90': '270'},
        radius: 50,
        isShowStart: true,
        duration: 500,
        easing: 'back.in',
    })
        .then({
            rotate: {'-90': '270'},
            strokeDashoffset: {'-125': '-250'},
            duration: 3000,
            easing: 'cubic.out',
            repeat: 1000,
        });
});