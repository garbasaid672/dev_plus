<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile;
use Filament\Schemas\Components\Component;

class Parametres extends EditProfile
{
    protected static ?string $title = 'Paramètres';

    protected function getNameFormComponent(): Component
    {
        return parent::getNameFormComponent()->label('Nom');
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()->label('Adresse e-mail');
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()->label('Nouveau mot de passe');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()->label('Confirmer le nouveau mot de passe');
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return parent::getCurrentPasswordFormComponent()->label('Mot de passe actuel');
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Enregistrer les modifications');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Annuler');
    }
}
