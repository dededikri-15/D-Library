#!/usr/bin/env python3
# Fix login button - add submit button to D-Library login form
with open('resources/views/auth/login.blade.php', 'r', encoding='utf8') as f:
    content = f.read()

old_text = '''                    <a href="{{ route('password.request') }}" class="text-sm text-tertiary hover:underline">{{ __('auth.forgot_password') }}</a>

                    <div class="hidden">

                    </div>'''

new_text = '''                    <a href="{{ route('password.request') }}" class="text-sm text-tertiary hover:underline">{{ __('auth.forgot_password') }}</a>

                    <div>
                        <button type="submit"
                                class="w-full flex justify-center rounded border border-primary px-4 py-2 text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ __('auth.login') }}
                        </button>
                    </div>'''

if old_text in content:
    content = content.replace(old_text, new_text)
    with open('resources/views/auth/login.blade.php', 'w', encoding='utf8') as f:
        f.write(content)
    print('SUCCESS: Login button added')
else:
    print('FAILED: Old text not found')
    # Debug: show relevant lines
    lines = content.split('\n')
    for i in range(20, 35):
        print(f'Line {i+1}: {repr(lines[i])}')