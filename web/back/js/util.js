
function size_encode({w, h}, validate = true) {
    if (validate) {
        if (w < 1 || w > 999) return false;
        if (h < 1 || h > 999) return false;
    }
    return `${h}x${w}`;
}

function size_decode(size) {
    if (!size.match(/^([0-9]+)x([0-9]+)$/)) return {w:0,h:0}

    const v = size.split('x')
    const h = parseInt(v[0])
    const w = parseInt(v[1])
    return {h,w}
}
