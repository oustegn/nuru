#!/usr/bin/env python3
"""Tests sur une base dédiée (_test/_validation), avec nettoyage des seuls objets temporaires."""
import html
import json
import os
import re
import secrets
import subprocess
from pathlib import Path
from urllib.parse import urlparse

import requests

ROOT = Path(__file__).resolve().parents[1]
BASE = os.environ.get('TEST_BASE_URL', 'http://127.0.0.1:3000').rstrip('/')
TAG = secrets.token_hex(6)
COUNT = 0


def check(condition, label):
    global COUNT
    if not condition:
        raise AssertionError(label)
    COUNT += 1
    print('OK : ' + label, flush=True)


def fixture(action):
    result = subprocess.run(
        ['php', 'tests/security-fixtures.php'], cwd=ROOT,
        input=json.dumps({'action': action, 'tag': TAG}),
        text=True, capture_output=True, check=True,
    )
    return json.loads(result.stdout)


def csrf(page):
    match = re.search(r'name="_csrf_token"\s+value="([^"]+)"', page)
    if not match:
        raise AssertionError('Token CSRF absent du formulaire')
    return html.unescape(match.group(1))


def get(session, path):
    return session.get(BASE + path, timeout=20, allow_redirects=False)


def login(session, credentials, **extra):
    page = get(session, '/connexion')
    check(page.status_code == 200, 'Formulaire de connexion accessible')
    data = dict(credentials)
    data['_csrf_token'] = csrf(page.text)
    data.update(extra)
    return session.post(BASE + '/connexion', data=data, timeout=20, allow_redirects=False)


admin_routes = ['/admin', '/admin/utilisateurs', '/admin/formations', '/admin/etablissements']
created = False
try:
    # Le helper refuse toute base non explicitement nommée comme base de test.
    created = True  # nettoie aussi les éventuels objets partiels si la préparation échoue
    data = fixture('create')
    credentials = data['credentials']
    anonymous = requests.Session()
    page = get(anonymous, '/connexion')
    check(page.status_code == 200 and csrf(page.text), 'Page de connexion avec CSRF')
    cookies = page.headers.get('Set-Cookie', '').lower()
    check('httponly' in cookies, 'Cookie de session HttpOnly')
    if BASE.startswith('https://'):
        check('secure' in cookies and 'samesite=none' in cookies, 'Cookie aperçu HTTPS Secure et SameSite=None')
    else:
        check('samesite=lax' in cookies, 'Cookie localhost SameSite=Lax')
    for path in admin_routes + ['/mon-espace', '/espace/apprenant', '/espace/formateur']:
        response = get(anonymous, path)
        check(response.status_code == 302 and urlparse(response.headers['Location']).path == '/connexion', 'Anonyme redirigé : ' + path)

    invalid = requests.Session()
    response = login(invalid, credentials['learner'], _csrf_token='invalide')
    check(response.status_code == 302 and get(invalid, '/mon-espace').status_code == 302, 'CSRF invalide : pas de session authentifiée')
    response = login(invalid, credentials['learner'], password='mot-de-passe-incorrect')
    check(response.status_code == 302 and get(invalid, '/mon-espace').status_code == 302, 'Mauvais mot de passe refusé')
    error_page = get(invalid, '/connexion')
    check('Connexion impossible.' in error_page.text and credentials['learner']['email'] not in error_page.text.split('Connexion impossible.')[1].split('</div>')[0], 'Erreur de connexion générique')

    for role, expected in [('learner', '/espace/apprenant'), ('teacher', '/espace/formateur'), ('admin', '/admin'), ('dual', '/espace/formateur')]:
        session = requests.Session()
        response = login(session, {'email': '  ' + credentials[role]['email'].upper() + '  ', 'password': credentials[role]['password']}, role='ROLE_ADMIN', _target_path='https://example.invalid')
        check(response.status_code == 302 and urlparse(response.headers['Location']).path == '/mon-espace', 'Connexion valide et redirection interne : ' + role)
        route = get(session, '/mon-espace')
        check(route.status_code == 302 and urlparse(route.headers['Location']).path == expected, 'Orientation selon le rôle : ' + role)
        workspace = get(session, expected)
        check(workspace.status_code == 200 and credentials[role]['email'] in workspace.text, 'Identité réelle du compte : ' + role)
        check('no-store' in workspace.headers.get('Cache-Control', '') and 'noindex' in workspace.headers.get('X-Robots-Tag', ''), 'Espace privé non caché et non indexable : ' + role)
        for admin_path in admin_routes:
            access = get(session, admin_path)
            check(access.status_code == (200 if role == 'admin' else 403), 'Protection admin ' + role + ' : ' + admin_path)
            if role == 'admin':
                check('password' not in access.text.lower() and '$2y$' not in access.text and '$argon2' not in access.text, 'Aucun hash affiché : ' + admin_path)
        check(get(session, '/espace/apprenant').status_code == (403 if role == 'teacher' else 200), 'Droits apprenant : ' + role)
        check(get(session, '/espace/formateur').status_code == (403 if role == 'learner' else 200), 'Droits formateur : ' + role)
        if role == 'teacher':
            check('Formation temporaire de sécurité' in workspace.text, 'Le formateur voit sa formation affectée')
        if role == 'dual':
            check('Formation temporaire de sécurité' not in workspace.text, 'Pas de fuite de formation affectée à un autre compte')
        if role == 'admin':
            search = get(session, '/admin/utilisateurs?q=' + credentials['teacher']['email'])
            check(search.status_code == 200 and credentials['teacher']['email'] in search.text, 'Recherche utilisateurs admin')
            malformed = get(session, '/admin/utilisateurs?page[]=1&q[]=x')
            check(malformed.status_code == 200, 'Paramètres de pagination non scalaires gérés')
            for list_path in ['/admin/formations', '/admin/etablissements']:
                first = get(session, list_path + '?page=1')
                second = get(session, list_path + '?page=2')
                first_rows = re.search(r'<tbody>(.*?)</tbody>', first.text, re.S).group(1).count('<tr>')
                second_rows = re.search(r'<tbody>(.*?)</tbody>', second.text, re.S).group(1).count('<tr>')
                check(first.status_code == 200 and first_rows == 20, 'Première page limitée à 20 lignes : ' + list_path)
                check(second.status_code == 200 and 0 < second_rows <= 20, 'Deuxième page non vide et limitée : ' + list_path)
                ids1 = set(re.findall('security-' + TAG + r'-\d+', first.text))
                ids2 = set(re.findall('security-' + TAG + r'-\d+', second.text))
                check(bool(ids1) and bool(ids2) and ids1.isdisjoint(ids2), 'Décalage effectif sans recouvrement : ' + list_path)
        before = get(session, expected)
        wrong_logout = session.post(BASE + '/deconnexion', data={'_csrf_token': 'invalide'}, timeout=20, allow_redirects=False)
        check(wrong_logout.status_code == 403 and get(session, expected).status_code == 200, 'Déconnexion sans CSRF valide refusée : ' + role)
        valid_logout = session.post(BASE + '/deconnexion', data={'_csrf_token': csrf(before.text)}, timeout=20, allow_redirects=False)
        check(valid_logout.status_code == 302 and get(session, expected).status_code == 302, 'Déconnexion valide : ' + role)

    disabled = requests.Session()
    response = login(disabled, credentials['disabled'])
    check(response.status_code == 302 and get(disabled, '/mon-espace').status_code == 302, 'Compte inactif refusé')
    suspended = requests.Session()
    response = login(suspended, credentials['learner'])
    check(response.status_code == 302 and get(suspended, '/espace/apprenant').status_code == 200, 'Session apprenant active avant suspension')
    fixture('disable-learner')
    check(get(suspended, '/espace/apprenant').status_code == 302, 'Compte devenu inactif : session déjà ouverte invalidée')
    check(get(anonymous, '/deconnexion').status_code == 405, 'Déconnexion GET impossible')
    for path in ['/demo/apprenant', '/demo/formateur', '/demo/etablissement']:
        check(get(anonymous, path).status_code == 200, 'Ancienne démonstration toujours publique : ' + path)
    print(f'{COUNT} contrôles de sécurité réussis.', flush=True)
finally:
    if created:
        fixture('cleanup')
        print('Objets de sécurité temporaires supprimés ; aucune donnée utilisateur existante modifiée.', flush=True)
