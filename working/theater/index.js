/* jshint node: true, esnext: true, undef: true, unused: true */
"use strict";

function getNextToken(string, options) {
    let token = '';

    let trimmed = false;
    while (!trimmed) {
        let whitespace = string.match(/[ \t\n\v\f\r]+/);

        if (whitespace && whitespace.index === 0) {
            string = string.slice(whitespace[0].length);
        }

        if (string.startsWith('//')) {
            string = string.slice(string.indexOf('\n'));
        }
        else {
            trimmed = true;
        }
    }

    if (string.startsWith('"')) {
        if (options.escapeCharacters) {
            let endFound = false;
            let index = 1;

            while (!endFound) {
                index = string.indexOf('"', index);

                if (index === -1) {
                    index = string.length;

                    endFound = true;
                }
                else if (string.charAt(index - 1) !== '\\') {
                    endFound = true;
                }
            }

            token = string.slice(0, index + 1);
            string = string.slice(index + 1);
        }
        else {
            let index = string.indexOf('"', 1);

            token = string.slice(0, index + 1);
            string = string.slice(index + 1);
        }
    }
    else if (string.startsWith('{') || string.startsWith('}')) {
        token = string.charAt(0);
        string = string.slice(1);
    }
    else {
        let stop = string.match(/[ \t\n\v\f\r"{}]/);

        if (stop) {
            token = string.slice(0, stop.index);
            string = string.slice(stop.index);
        }
        else {
            token = string;
            string = '';
        }
    }

    return {
        token: token,
        remainder: string
    };
}

function escapeString(value, options) {
    if (options.escapeCharacters) {
        value = value.replace(/\\/g, '\\\\');
    }

    value = value.replace(/"/g, '\\"');

    return value;
}

function parseQuotedValue(value, options) {
    value = value.slice(1, -1);

    if (options.escapeCharacters) {
        value = value.replace(/\\n/g, '\n');
        value = value.replace(/\\t/g, '\t');
        value = value.replace(/\\v/g, '\v');
        value = value.replace(/\\b/g, '\b');
        value = value.replace(/\\r/g, '\r');
        value = value.replace(/\\f/g, '\f');
        value = value.replace(/\\a/g, '\a');
        value = value.replace(/\\\\/g, '\\');
        value = value.replace(/\\\?/g, '?');
        value = value.replace(/\\'/g, '\'');
        value = value.replace(/\\"/g, '"');
    }

    return value;
}

function checkConditional(conditional, conditions) {
     if (conditional.startsWith('[') && conditional.endsWith(']')) {
         conditional = conditional.slice(1, -1);
         let negate = false;

         if (conditional.startsWith('!')) {
             negate = true;
             conditional = conditional.slice(1);
         }

         return (conditions.indexOf(conditional) !== -1) ^ negate;
     }
 }

class KeyValues {
    constructor(key, value) {
        this.key = key || "";
        this.value = value || "";
    }

    load(kvString, options) {
        options = options || {};
        options.escapeCharacters = options.hasOwnProperty('escapeCharacters') ? options.escapeCharacters : true;
        options.evaluateConditionals = options.hasOwnProperty('evaluateConditionals') ? options.evaluateConditionals : false;
        options.conditions = options.hasOwnProperty('conditions') ? options.conditions : [];
        options.retrieveKeyValueFile = options.hasOwnProperty('retrieveKeyValueFile') ? options.retrieveKeyValueFile : null;

        let finished = false;
        let includes = [];
        let bases = [];
        let extracted = [];

        while (!finished) {
            let results = getNextToken(kvString, options);

            if (results.token === '') {
                finished = true;

                break;
            }

            if (results.token === '#include') {
                results = getNextToken(results.remainder);

                if (!results.token) {
                    throw new Error('#include is null');
                }

                if (options.retrieveKeyValueFile) {
                    includes.push(options.retrieveKeyValueFile(results.token));
                }

                kvString = results.remainder;
            }
            else if (results.token === '#base') {
                results = getNextToken(results.remainder);

                if (!results.token) {
                    throw new Error('#base is null');
                }

                if (options.retrieveKeyValueFile) {
                    bases.push(options.retrieveKeyValueFile(results.token));
                }

                kvString = results.remainder;
            }
            else {
                let key = results.token;

                if (key.startsWith('"') && key.endsWith('"')) {
                    key = parseQuotedValue(key, options);
                }

                kvString = results.remainder;
                let accepted = true;
                results = getNextToken(kvString, options);

                if (results.token.startsWith('[') && results.token.endsWith(']')) {
                    if (options.evaluateConditionals) {
                        accepted = checkConditional(results.token, options.conditions);
                    }

                    kvString = results.remainder;
                    results = getNextToken(kvString, options);
                }

                if (results.token !== '{') {
                    throw new Error('missing {');
                }

                results = this.recursiveLoad(kvString, options);
                let value = results.extracted;
                kvString = results.remainder;

                if (accepted) {
                    extracted.push(new KeyValues(key, value));
                }
            }
        }

        extracted = extracted.concat(includes);
        bases.forEach(function(base) {
            extracted[0].mergeKeys(base);
        });

        if (extracted.length === 1) {
            this.key = extracted[0].key;
            this.value = extracted[0].value;
        }
        else {
            this.value = extracted;
        }
    }

    recursiveLoad(kvString, options) {
        let finished = false;
        let extracted = [];

        let opening = getNextToken(kvString, options);
        kvString = opening.remainder;

        while (!finished) {
            let results = getNextToken(kvString, options);

            if (results.token === '') {
                throw new Error('got null key');
            }

            if (results.token === '}') {
                finished = true;

                break;
            }

            let key = results.token;

            if (key.startsWith('"') && key.endsWith('"')) {
                key = parseQuotedValue(key, options);
            }

            kvString = results.remainder;
            let accepted = true;
            results = getNextToken(kvString, options);

            if (results.token.startsWith('[') && results.token.endsWith(']')) {
                if (options.evaluateConditionals) {
                    accepted = checkConditional(results.token, options.conditions);
                }

                kvString = results.remainder;
                results = getNextToken(kvString, options);
            }

            let value = results.token;

            if (value === null) {
                throw new Error('got null value');
            }

            if (value === '}') {
                throw new Error('got } in value');
            }

            if (value.startsWith('[') && value.endsWith(']')) {
                throw new Error('got conditional instead of value');
            }

            if (value.startsWith('"') && value.endsWith('"')) {
                value = parseQuotedValue(value, options);
            }

            if (value === '{') {
                results = this.recursiveLoad(kvString, options);
                value = results.extracted;
                kvString = results.remainder;
            }
            else {
                // TODO: parse values?

                kvString = results.remainder;
                results = getNextToken(kvString, options);

                if (results.token.startsWith('[') && results.token.endsWith(']')) {
                    if (options.evaluateConditionals) {
                        accepted = checkConditional(results.token, options.conditions);
                    }

                    kvString = results.remainder;
                    results = getNextToken(kvString, options);
                }
            }

            if (accepted) {
                extracted.push(new KeyValues(key, value));
            }
        }

        let closing = getNextToken(kvString, options);
        kvString = closing.remainder;

        return {
            extracted: extracted,
            remainder: kvString
        };
    }

    save(options) {
        options = options || {};
        options.escapeCharacters = options.hasOwnProperty('escapeCharacters') ? options.escapeCharacters : true;
        options.sortKeys = options.hasOwnProperty('sortKeys') ? options.sortKeys : false;
        options.allowEmptyStrings = options.hasOwnProperty('allowEmptyStrings') ? options.allowEmptyStrings : false;

        return this.recursiveSave(0, options);
    }

    recursiveSave(depth, options) {
        let kvString = '';

        if (Array.isArray(this.value)) {
            let subKeys = this.value.slice();

            if (options.sortKeys) {
                subKeys.sort(function(a, b) {
                    if (!(a instanceof KeyValues) && !(b instanceof KeyValues)) {
                        return 0;
                    }

                    if (!(a instanceof KeyValues)) {
                        return -1;
                    }

                    if (!(b instanceof KeyValues)) {
                        return 1;
                    }

                    let firstKey = a.key.toLowerCase();
                    let secondKey = b.key.toLowerCase();

                    return (firstKey < secondKey) ? -1 : ((firstKey > secondKey) ? 1 : 0);
                });
            }

            for (let i = 0; i < depth; i++) {
                kvString += '\t';
            }
            kvString += '"' + escapeString(this.key, options) + '"' + '\n';
            for (let i = 0; i < depth; i++) {
                kvString += '\t';
            }
            kvString += '{' + '\n';

            subKeys.forEach(function(subKey) {
                if (subKey instanceof KeyValues) {
                    kvString += subKey.recursiveSave(depth + 1, options);
                }
            });

            for (let i = 0; i < depth; i++) {
                kvString += '\t';
            }
            kvString += '}' + '\n';
        }
        else if (options.allowEmptyStrings === true || this.value !== '') {
            for (let i = 0; i < depth; i++) {
                kvString += '\t';
            }
            kvString += '"' + escapeString(this.key, options) + '"' + '\t\t' + '"' + escapeString(this.value, options) + '"' + '\n';
        }

        return kvString;
    }

    mergeKeys(kv) {
        if (Array.isArray(kv.value)) {
            kv.value.forEach(function(source) {
                if (source instanceof KeyValues) {
                    let destination = this.findKey(source.key);

                    if (destination) {
                        destination.mergeKeys(source);
                    }
                    else {
                        this.addSubKey(source.key, source.value);
                    }
                }
            });
        }
    }

    findKey(key) {
        let result = null;

        if (Array.isArray(this.value)) {
            for (let i = 0; i < this.value.length; i++) {
                let current = this.value[i];

                if (current instanceof KeyValues && current.key === key) {
                    result = current;

                    break;
                }
            }
        }

        return result;
    }

    addSubKey(key, value) {
        if (!Array.isArray(this.value)) {
            throw new Error('does not have subkeys');
        }

        this.value.push(new KeyValues(key, value));
    }
}

module.exports = KeyValues;
