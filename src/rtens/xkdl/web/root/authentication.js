var authentication = authentication  || {};

authentication.hash = function (a, b) {
    return CryptoJS.MD5(a + b) + '';
};

authentication.respond = function (challenge) {
    var response = authentication.hash(localStorage.getItem('token'), challenge);
    var d = new Date();
    d.setTime(d.getTime() + 7*24*3600000);

    var cookie = "response=" + response + "; expires=" + d.toUTCString() + "; path=/";
    console.log(cookie);
    document.cookie = cookie;
};