<x-mail::message>
# Bienvenue sur Horaires Socar, {{ $employee->name }} !

Votre compte a été créé par votre responsable. Voici vos identifiants de connexion :

**Email :** {{ $employee->email }}
**Mot de passe temporaire :** `{{ $temporaryPassword }}`

<x-mail::button :url="$loginUrl">
Se connecter
</x-mail::button>

**Important :** Pour votre sécurité, veuillez changer votre mot de passe dès votre première connexion via votre profil.

Cordialement,
L'équipe Socar
</x-mail::message>
