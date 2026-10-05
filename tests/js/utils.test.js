import assert from 'node:assert/strict';
import test from 'node:test';
import { numberValue, stringValue } from '../../resources/js/Utils/index.js';

test('stock quantities and totals use the first available numeric field', () => {
    assert.equal(numberValue(8, undefined, undefined), 8);
    assert.equal(numberValue(undefined, null, '80.50'), 80.5);
    assert.equal(numberValue(0, 99), 0);
    assert.equal(numberValue(null, -3), -3);
});

test('missing or non-finite numeric values render as zero', () => {
    assert.equal(numberValue(), 0);
    assert.equal(numberValue(undefined, null), 0);
    assert.equal(numberValue('invalid'), 0);
    assert.equal(numberValue(Infinity), 0);
});

test('text fallback selects one field without joining unrelated values', () => {
    assert.equal(stringValue(undefined, null, 'Inventory', 'Fallback'), 'Inventory');
    assert.equal(stringValue('Primary', 'Secondary'), 'Primary');
    assert.equal(stringValue(0, 'Fallback'), '0');
});

test('empty text is preserved and missing text has an empty default', () => {
    assert.equal(stringValue('', 'Fallback'), '');
    assert.equal(stringValue(), '');
    assert.equal(stringValue(undefined, null), '');
});
