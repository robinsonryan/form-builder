import { setStrictRendering, resolveWidgetForField, clearWidgets } from '../widgetRegistry.js';

describe('widgetRegistry strict mode', () => {
  afterEach(() => {
    clearWidgets();
  });

  test('strictRendering=true causes thrown errors for unknown explicit ui:widget', () => {
    setStrictRendering(true);
    expect(() => {
      resolveWidgetForField({ type: 'string' }, { 'ui:widget': 'unknown:Widget' });
    }).toThrow(/Unknown explicit ui:widget/);
  });

  test('strictRendering=false returns null for unknown explicit ui:widget', () => {
    setStrictRendering(false);
    const res = resolveWidgetForField({ type: 'string' }, { 'ui:widget': 'unknown:Widget' });
    expect(res).toBeNull();
  });
});
