import { beforeEach, describe, it, expect } from 'vitest';
import { clearWidgets, resolveWidgetForField, resolveWidget } from '../widgetRegistry.js';
import { registerCoreWidgets } from '../registerCoreWidgets.js';

describe('coreWidgets registration', () => {
  beforeEach(() => {
    clearWidgets();
  });

  it('registerCoreWidgets populates registry with core:* keys', () => {
    registerCoreWidgets();
    expect(resolveWidget('core:InputText')).toBeDefined();
    expect(resolveWidget('core:Textarea')).toBeDefined();
    expect(resolveWidget('core:Checkbox')).toBeDefined();
    expect(resolveWidget('core:Stepper')).toBeDefined();
  });

  it('resolveWidgetForField falls back to core primitives when ui:widget absent', () => {
    registerCoreWidgets();
    const res = resolveWidgetForField({ type: 'string' }, {});
    expect(res).not.toBeNull();
    // because registerCoreWidgets registers 'string' -> InputText, the resolved name is 'string'
    expect(res.name).toBe('string');
    expect(res.widget).toBe(resolveWidget('string'));
  });
});
