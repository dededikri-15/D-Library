        {{--
            Kategori/penulis/penerbit + status. Dipakai BERSAMA oleh form
            tambah dan form edit; karenanya `:value` selalu menerima
            `$book->*` — pada form tambah `$book` masih kosong sehingga
            hasil rendernya sama persis dengan tidak menulis `:value`.

            `$statusValue` berbeda antar halaman: form tambah memakai
            `App\Models\Book::STATUS_AVAILABLE`, form edit memakai
            `$book->status`.
        --}}
        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <x-form.select name="category_id" :label="__('book_form.category')" required :emptyLabel="__('book_form.choose_category')"
                               :options="$categories->pluck('name', 'id')->all()" :value="$book->category_id"
                               data-quick-add data-quick-type="category" data-quick-url="{{ route('categories.quick') }}" />
            </div>
            <div>
                <x-form.select name="author_id" :label="__('book_form.author')" required :emptyLabel="__('book_form.choose_author')"
                               :options="$authors->pluck('name', 'id')->all()" :value="$book->author_id"
                               data-quick-add data-quick-type="author" data-quick-url="{{ route('authors.quick') }}" />
            </div>
            <div>
                <x-form.select name="publisher_id" :label="__('book_form.publisher')" required :emptyLabel="__('book_form.choose_publisher')"
                               :options="$publishers->pluck('name', 'id')->all()" :value="$book->publisher_id"
                               data-quick-add data-quick-type="publisher" data-quick-url="{{ route('publishers.quick') }}" />
            </div>
        </div>

        <x-form.select name="status" :label="__('book_form.status')" required :allowEmpty="false"
                       :options="App\Models\Book::statusOptions()" :value="$statusValue" />