var assert = require('assert');
var fs = require('fs');
var path = require('path');

function readProjectFile(filePath) {
    return fs.readFileSync(path.join(__dirname, '..', filePath), 'utf8');
}

function assertFileContains(filePath, expected) {
    var contents = readProjectFile(filePath);

    assert(contents.length > 0, filePath + ' should not be empty');
    assert(
        contents.indexOf(expected) !== -1,
        filePath + ' should contain "' + expected + '"'
    );
}

assertFileContains('web/css/style.css', '.navbar');
assertFileContains('web/js/all-scripts.js', 'jQuery');

var baseTemplate = readProjectFile('app/Resources/views/base.html.twig');

assert(
    baseTemplate.indexOf("{{ asset('css/style.css') }}") !== -1,
    'base template should load css/style.css'
);
assert(
    baseTemplate.indexOf("{{ asset('js/all-scripts.js') }}") !== -1,
    'base template should load js/all-scripts.js'
);

console.log('Frontend smoke checks passed.');
