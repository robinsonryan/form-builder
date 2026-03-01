import { describe, it, expect, beforeEach } from 'vitest';
import { useForm } from '../useForm';
import { registerWidget, resolveWidget, clearWidgets } from '../widgetRegistry';

describe('renderer-core / useForm', () => {
  it('exports initialize, setField, getAnswers', () => {
    const form = useForm({ initial: true });
    expect(typeof form.initialize).toBe('function');
    expect(typeof form.setField).toBe('function');
    expect(typeof form.getAnswers).toBe('function');
  });

  it('setField sets deep values and getAnswers returns a clone', () => {
    const form = useForm({ user: { name: 'old' } });
    form.setField('user.name', 'Alice');
    expect(form.getAnswers()).toEqual({ user: { name: 'Alice' } });

    // Mutating returned object should not affect internal state
    const snapshot = form.getAnswers();
    snapshot.user.name = 'Bob';
    expect(form.getAnswers().user.name).toBe('Alice');
  });
});

describe('renderer-core / widgetRegistry', () => {
  beforeEach(() => {
    clearWidgets();
  });

  it('can register and resolve a dummy widget', () => {
    const dummy = { render: () => null };
    registerWidget('dummy', dummy);
    expect(resolveWidget('dummy')).toBe(dummy);
  });

  it('throws when registering without a name', () => {
    expect(() => registerWidget('', {})).toThrow();
  });
});
