<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de retrait</title>
</head>
<body>
    <main>
        <h1>Demande de retrait</h1>
        <p>Solde disponible : {{ number_format($balance, 0, ',', ' ') }} FCFA</p>

        @if (session('success'))
            <p role="status">{{ session('success') }}</p>
        @endif

        <form method="POST" action="{{ route('withdrawal.store') }}">
            @csrf

            <label for="amount">Montant (FCFA)</label>
            <input id="amount" name="amount" type="number" min="1000" step="1" value="{{ old('amount') }}" required>
            @error('amount')
                <p role="alert">{{ $message }}</p>
            @enderror

            <label for="method">Opérateur</label>
            <select id="method" name="method" required>
                <option value="">Choisir un opérateur</option>
                @foreach ($methods as $value => $label)
                    <option value="{{ $value }}" @selected(old('method') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('method')
                <p role="alert">{{ $message }}</p>
            @enderror

            <label for="account_details">Numéro de paiement</label>
            <input id="account_details" name="account_details" type="tel" maxlength="255" value="{{ old('account_details') }}" required>
            @error('account_details')
                <p role="alert">{{ $message }}</p>
            @enderror

            <button type="submit">Envoyer la demande</button>
        </form>
    </main>
</body>
</html>
