import { beforeEach, describe, it, expect } from 'vitest';
import {
  clearWidgets,
  registerWidget,
  registerTypeMapping,
  resolveWidgetForField,
  setStrictRendering,
} from '../widgetRegistry.js';
import { registerCoreWidgets } from '../registerCoreWidgets.js';

describe('field renderer resolution (registry-backed)', () => {
  beforeEach(() => {
    clearWidgets();
    // default strict mode for these tests; individual tests override as needed
    setStrictRendering(true);
  });

  it('resolves explicit ui:widget when registered', () => {
    const widget = () => 'prime';
    registerWidget('prime:InputText', widget);

    const res = resolveWidgetForField({}, { 'ui:widget': 'prime:InputText' });
    expect(res).toBeDefined();
    expect(res.name).toBe('prime:InputText');
    expect(res.widget).toBe(widget);
  });

  it('falls back to core primitive when ui:widget absent', () => {
    // register the tiny core primitives
    registerCoreWidgets();

    const res = resolveWidgetForField({ type: 'string' }, {});
    expect(res).not.toBeNull();
    // registerCoreWidgets registers a raw 'string' key pointing to the core InputText
    expect(res.name).toBe('string');
    expect(res.widget).toBeDefined();
  });

  it('throws when ui:widget unknown and strictRendering enabled', () => {
    setStrictRendering(true);
    expect(() => resolveWidgetForField({ type: 'string' }, { 'ui:widget': 'unknown:Widget' })).toThrow();
  });

  it('uses type mapping when provided by adapter', () => {
    const widget = () => 'prime';
    registerWidget('prime:InputText', widget);
    registerTypeMapping('string', 'prime:InputText');

    const res = resolveWidgetForField({ type: 'string' }, {});
    expect(res).toBeDefined();
    expect(res.name).toBe('prime:InputText');
    expect(res.widget).toBe(widget);
  });

  it('returns null for mapping target not yet registered (non-throwing)', () => {
    // mapping exists but target widget is not registered
    registerTypeMapping('string', 'prime:InputText');
    const res = resolveWidgetForField({ type: 'string' }, {});
    expect(res).toBeNull();
  });
});
