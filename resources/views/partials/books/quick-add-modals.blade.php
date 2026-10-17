    {{--
        Modal quick-add (kategori/penulis/penerbit) harus DI LUAR form utama.

        HTML tidak boleh punya <form> di dalam <form>: browser membuang tag
        <form> kedua beserta atributnya, lalu input `name` yang `required` di
        dalam modal ikut dimiliki form utama. Karena modalnya tertutup
        (`display: none`), validasi browser menolak mengirim form utama dengan
        "An invalid form control with name='name' is not focusable" — tombol
        "Simpan buku" jadi tidak bereaksi sama sekali.
    --}}
    <x-modal id="modal-kategori" :title="__('book_form.quick_category')">
        <form method="POST" action="{{ route('categories.quick') }}" data-quick-form data-quick-target="category_id">
            @csrf
            <x-form.input id="quick-category-name" name="name" :label="__('book_form.category_name')" required />
            <p data-quick-error class="mt-1 hidden text-label text-overdue" role="alert"></p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('book_form.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('book_form.quick_save') }}</button>
            </div>
        </form>
    </x-modal>

    <x-modal id="modal-penulis" :title="__('book_form.quick_author')">
        <form method="POST" action="{{ route('authors.quick') }}" data-quick-form data-quick-target="author_id">
            @csrf
            <x-form.input id="quick-author-name" name="name" :label="__('book_form.author_name')" required />
            <p data-quick-error class="mt-1 hidden text-label text-overdue" role="alert"></p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('book_form.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('book_form.quick_save') }}</button>
            </div>
        </form>
    </x-modal>

    <x-modal id="modal-penerbit" :title="__('book_form.quick_publisher')">
        <form method="POST" action="{{ route('publishers.quick') }}" data-quick-form data-quick-target="publisher_id">
            @csrf
            <x-form.input id="quick-publisher-name" name="name" :label="__('book_form.publisher_name')" required />
            <p data-quick-error class="mt-1 hidden text-label text-overdue" role="alert"></p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('book_form.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('book_form.quick_save') }}</button>
            </div>
        </form>
    </x-modal>
