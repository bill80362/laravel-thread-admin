<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\EditProfile;
use Filament\Schemas\Schema;

class EditPassword extends EditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getCurrentPasswordFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
        ]);
    }
}
