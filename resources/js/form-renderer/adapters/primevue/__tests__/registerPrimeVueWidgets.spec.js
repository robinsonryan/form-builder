import { beforeEach, describe, it, expect } from 'vitest';
import { clearWidgets, resolveWidget, resolveTypeMapping } from '../../../core/widgetRegistry.js';
import { registerPrimeVueWidgets } from '../registerPrimeVueWidgets.js';

describe('registerPrimeVueWidgets', () => {
  beforeEach(() => {
    clearWidgets();
  });

  it('registers prime:* widget keys when components provided and sets default string mapping', () => {
    const DummyInput = () => {};
    const DummyTextarea = () => {};

    registerPrimeVueWidgets({
      InputText: DummyInput,
      Textarea: DummyTextarea,
    });

    expect(resolveWidget('prime:InputText')).toBeDefined();
    expect(resolveWidget('prime:Textarea')).toBeDefined();

    // Default behavior: when InputText is provided, mapping for 'string' -> 'prime:InputText' is set
    expect(resolveTypeMapping('string')).toBe('prime:InputText');
  });

  it('allows overriding the default string mapping via options', () => {
    const DummyInput = () => {};

    registerPrimeVueWidgets(
      { InputText: DummyInput },
      { string: 'my:CustomStringWidget' }
    );

    // mapping should be the provided override
    expect(resolveTypeMapping('string')).toBe('my:CustomStringWidget');
    // the override target is not automatically registered as a widget unless provided
    expect(resolveWidget('my:CustomStringWidget')).toBeUndefined();
  });

  it('registers Stepper when provided', () => {
    const DummyStepper = () => {};
    registerPrimeVueWidgets({ Stepper: DummyStepper });
    expect(resolveWidget('prime:Stepper')).toBeDefined();
  });
});
