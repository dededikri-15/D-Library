        <x-form.input name="title" :label="__('book_form.title')" required :value="$book->title" />
        <x-form.input name="isbn" :label="__('book_form.isbn')" required :value="$book->isbn" />

        <x-form.textarea name="description" :label="__('book_form.description')" rows="4" :value="$book->description" />

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="publication_year" :label="__('book_form.publication_year')" type="number" required
                          :value="$book->publication_year" />
            <x-form.input name="pages" :label="__('book_form.pages')" type="number" :value="$book->pages" />
        </div>
