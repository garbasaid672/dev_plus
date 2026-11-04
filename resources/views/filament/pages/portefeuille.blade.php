<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-3">
        <x-filament::section>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Revenus confirmés</p>
            <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                {{ number_format($confirmedRevenue, 0, ',', ' ') }} FCFA
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Commandes terminées</p>
        </x-filament::section>

        <x-filament::section>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total des achats</p>
            <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                {{ number_format($purchaseTotal, 0, ',', ' ') }} FCFA
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Commandes non annulées</p>
        </x-filament::section>

        <x-filament::section>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Commandes enregistrées</p>
            <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                {{ number_format($orderCount, 0, ',', ' ') }}
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tous statuts confondus</p>
        </x-filament::section>
    </div>

    <x-filament::section heading="Achats" description="Détail des commandes et de leur montant.">
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
