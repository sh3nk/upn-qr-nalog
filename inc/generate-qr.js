(function($) {
    var canvas = $('canvas#uq-qrcode')[0];

    var ECL = qrcodegen.QrCode.Ecc.MEDIUM;
    var version = 15;
    var mask = -1;
    var boostEcl = false;
    var data = $('#uq-data').data('value');

    var segECI = qrcodegen.QrSegment.makeEci(4);
    var segments = qrcodegen.QrSegment.makeBytes(objectToArray(data));

    // combine data with ECI header segment
    segments = [segECI, segments];

    // encodeSegments(segments, ECL, minVersion, maxVersion, mask, boostEcl)
    var qr = qrcodegen.QrCode.encodeSegments(segments, ECL, version, version, mask, boostEcl);

    // drawCanvas(scale, border, canvasElement)
    qr.drawCanvas(1.6, 4, canvas);


    // Convert object (from PHP) to array
    function objectToArray() {
        var dataBytes = [];
        for (var c in data) {
            if (data.hasOwnProperty(c)) {
                dataBytes.push(data[c]);
            }
        }
        return dataBytes;
    }

})(jQuery);
