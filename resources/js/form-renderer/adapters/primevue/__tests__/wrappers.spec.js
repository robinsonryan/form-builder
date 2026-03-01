import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createInputWrapper } from '../wrappers/createInputWrapper.js';

describe('PrimeVue wrappers - createInputWrapper', () => {
  it('forwards initial modelValue to the inner Prime component', async () => {
    const DummyPrime = {
      props: ['modelValue', 'placeholder'],
      template: '<div><span data-test="value">{{ modelValue }}</span><span data-test="ph">{{ placeholder }}</span></div>',
    };

    const Wrapped = createInputWrapper(DummyPrime);
    const wrapper = mount(Wrapped, {
      props: {
        modelValue: 'initial',
        uiOptions: { placeholder: 'enter text' },
      },
    });

    expect(wrapper.find('[data-test="value"]').text()).toBe('initial');
    expect(wrapper.find('[data-test="ph"]').text()).toBe('enter text');
  });

  it('re-emits update:modelValue when inner Prime component emits update:modelValue', async () => {
    const DummyPrime = {
      props: ['modelValue'],
      template: '<div><button data-test="emit" @click="$emit(\'update:modelValue\', \'from-child\')">emit</button></div>',
    };

    const Wrapped = createInputWrapper(DummyPrime);
    const wrapper = mount(Wrapped, {
      props: {
        modelValue: 'start',
      },
    });

    await wrapper.find('[data-test="emit"]').trigger('click');

    const emitted = wrapper.emitted()['update:modelValue'];
    expect(emitted).toBeDefined();
    expect(emitted[0]).toEqual(['from-child']);
  });
});
