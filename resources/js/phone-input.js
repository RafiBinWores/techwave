import intlTelInput from 'intl-tel-input/intlTelInputWithUtils';
import 'intl-tel-input/styles';

document.addEventListener('alpine:init', () => {
    window.Alpine.store('phoneInputs', {
        instances: {},

        register(wirePhone, instance) {
            this.instances[wirePhone] = instance;
        },

        unregister(wirePhone) {
            delete this.instances[wirePhone];
        },

        sync(wirePhone) {
            const instance = this.instances[wirePhone];

            if (instance && typeof instance.sync === 'function') {
                instance.sync();
            }
        },

        syncAll() {
            Object.values(this.instances).forEach((instance) => {
                if (typeof instance.sync === 'function') {
                    instance.sync();
                }
            });
        },
    });

    window.Alpine.data('phoneInput', (wirePhone, wireCountry) => {
        let iti = null;

        return {
            unwatchPhone: null,
            unwatchCountry: null,
            hiddenPhone: null,
            hiddenCountry: null,

            init() {
                window.Alpine.store('phoneInputs').register(wirePhone, this);

                this.$nextTick(() => {
                    const input = this.$refs.input;
                    this.hiddenPhone = this.$refs.phone;
                    this.hiddenCountry = this.$refs.country;

                    if (!input || !this.hiddenPhone || !this.hiddenCountry) return;

                    const initialPhone = this.hiddenPhone.value || '';
                    const initialCountry = (this.hiddenCountry.value || 'BD').toUpperCase();

                    iti = intlTelInput(input, {
                        initialCountry: initialCountry.toLowerCase(),
                        separateDialCode: true,
                        placeholderNumberPolicy: 'POLITE',
                        placeholderNumberType: 'MOBILE',
                        countrySearch: true,
                    });

                    if (initialPhone) {
                        iti.setNumber(initialPhone);
                    }

                    if (this.$wire) {
                        this.unwatchPhone = this.$wire.$watch(wirePhone, (value) => {
                            if (iti && value && iti.getNumber() !== value) {
                                iti.setNumber(value);
                            }
                        });

                        this.unwatchCountry = this.$wire.$watch(wireCountry, (value) => {
                            if (iti && value && iti.getSelectedCountry().iso2.toUpperCase() !== value.toUpperCase()) {
                                iti.setSelectedCountry(value.toLowerCase());
                            }
                        });
                    }
                });
            },

            sync() {
                if (!iti) return;

                const iso2 = iti.getSelectedCountry().iso2.toUpperCase();
                const number = iti.getNumber();

                if (iso2 && iso2 !== this.hiddenCountry.value) {
                    this.hiddenCountry.value = iso2;
                }

                if (number !== this.hiddenPhone.value) {
                    this.hiddenPhone.value = number;
                }

                if (this.$wire) {
                    if (iso2) {
                        this.$wire.set(wireCountry, iso2, false);
                    }

                    this.$wire.set(wirePhone, number, false);
                }
            },

            destroy() {
                window.Alpine.store('phoneInputs').unregister(wirePhone);

                if (iti) {
                    iti.destroy();
                    iti = null;
                }

                if (typeof this.unwatchPhone === 'function') {
                    this.unwatchPhone();
                }

                if (typeof this.unwatchCountry === 'function') {
                    this.unwatchCountry();
                }
            },
        };
    });
});