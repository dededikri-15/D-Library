        <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-dashboard.monthly-chart :items="$myLoansPerMonth"
                                       :title="__('dashboard.my_loans_chart_title')"
                                       empty-description-key="dashboard.my_loans_chart_empty_description" />

            <x-dashboard.monthly-chart :items="$myReadingStartsPerMonth"
                                       :title="__('dashboard.reading_chart_title')"
                                       total-key="dashboard.reading_chart_total"
                                       alt-key="dashboard.reading_chart_alt"
                                       empty-title-key="dashboard.reading_chart_empty"
                                       empty-description-key="dashboard.reading_chart_empty_description"
                                       column-key="dashboard.chart_reading" />
        </div>
