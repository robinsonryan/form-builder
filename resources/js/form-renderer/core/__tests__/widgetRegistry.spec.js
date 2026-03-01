import { beforeEach, describe, it, expect } from 'vitest';
import {
  registerWidget,
  resolveWidget,
  registerTypeMapping,
  resolveWidgetForField,
  setStrictRendering,
  clearWidgets,
} from '../widgetRegistry.js';

describe('widgetRegistry', () => {
  beforeEach(() => {
    clearWidgets();
    setStrictRendering(true);
  });

  it('register/resolve round-trips correctly', () => {
    const w = () => 'ok';
    registerWidget('foo', w);
    expect(resolveWidget('foo')).toBe(w);
  });

  it('type mappings influence resolveWidgetForField when ui:widget is absent', () => {
    const w = () => 'ok';
    registerWidget('prime:InputText', w);
    registerTypeMapping('string', 'prime:InputText');
    const res = resolveWidgetForField({ type: 'string' }, {});
    expect(res).toBeDefined();
    expect(res.name).toBe('prime:InputText');
    expect(res.widget).toBe(w);
  });

  it('strictRendering causes resolveWidgetForField to throw on unknown explicit ui:widget', () => {
    setStrictRendering(true);
    expect(() =>
      resolveWidgetForField({ type: 'string' }, { 'ui:widget': 'unknown:Widget' })
    ).toThrow();
  });

  it('non-strict returns null for unknown explicit ui:widget', () => {
    setStrictRendering(false);
    expect(resolveWidgetForField({ type: 'string' }, { 'ui:widget': 'unknown:Widget' })).toBeNull();
  });
});
