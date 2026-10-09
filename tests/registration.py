#!/usr/bin/env python3
"""Parcours d’inscription sur base dédiée, SMTP localhost de capture, nettoyage ciblé."""
import html
import json
import os
import re
import secrets
import subprocess
from pathlib import Path
from urllib.parse import urlparse

import requests
from bs4 import BeautifulSoup

ROOT = Path(__file__).resolve().parents[1]
BASE = os.environ.get('TEST_BASE_URL', 'http://127.0.0.1:3000').rstrip('/')
MAILBOX = os.environ.get('TEST_MAILBOX_URL', 'http://127.0.0.1:1081')
TAG = secrets.token_hex(6)
ADMIN_TAG = secrets.token_hex(6)
COUNT = 0
EMAILS = []


def check(value, label):
    global COUNT
    if not value:
        raise AssertionError(label)
    COUNT += 1
    print('OK : ' + label, flush=True)


def fixture(email, action='inspect'):
    r = subprocess.run(['php', 'tests/registration-fixtures.php'], cwd=ROOT,
                       input=json.dumps({'email': email, 'action': action}), text=True, capture_output=True, check=True)
    return json.loads(r.stdout)


def admin_fixture(action):
    r = subprocess.run(['php', 'tests/security-fixtures.php'], cwd=ROOT,
                       input=json.dumps({'tag': ADMIN_TAG, 'action': action}), text=True, capture_output=True, check=True)
    return json.loads(r.stdout)


def get(session, path):
    return session.get(BASE + path, timeout=20, allow_redirects=False)


def inputs(body, form_name=None):
    soup = BeautifulSoup(body, 'html.parser')
    form = soup.find('form', attrs={'name': form_name}) if form_name else soup.find('form')
    if form is None:
        raise AssertionError('Formulaire absent')
    return {node['name']: node.get('value', '') for node in form.find_all('input') if node.get('name')}


def login(session, creds):
    page = get(session, '/connexion')
    values = inputs(page.text)
    values.update(creds)
    return session.post(BASE + '/connexion', data=values, timeout=20, allow_redirects=False)


def register(profile, label, extras=None):
    session = requests.Session()
    page = get(session, '/inscription/' + profile)
    check(page.status_code == 200, 'Formulaire public ' + profile)
    email = f'registration-{TAG}-{label}@nuru.invalid'
    EMAILS.append(email)
    password = secrets.token_hex(20)
    values = inputs(page.text, 'registration')
    values.update({'registration[prenom]': 'Test', 'registration[nom]': label,
                   'registration[email]': email,
                   'registration[plainPassword][first]': password,
                   'registration[plainPassword][second]': password,
                   'registration[telephone]': ''})
    if profile == 'formateur':
        values.update({'registration[specialite]': 'Symfony', 'registration[presentation]': 'Expérience pédagogique de test.'})
    if profile == 'etablissement':
        values.update({'registration[etablissementNom]': 'Établissement test ' + TAG,
                       'registration[etablissementAdresse]': 'Adresse test', 'registration[etablissementVille]': 'Conakry',
                       'registration[etablissementSite]': ''})
    if extras:
        values.update(extras)
    response = session.post(BASE + '/inscription/' + profile, data=values, timeout=20, allow_redirects=False)
    check(response.status_code == 302 and urlparse(response.headers['Location']).path == '/inscription/confirmation', 'Inscription enregistrée ' + label)
    record = fixture(email)
    check(not record['active'] and not record['verified'] and record['hasPasswordHash'], 'Compte inactif et mot de passe haché ' + label)
    check('ROLE_ADMIN' not in record['roles'], 'Aucun privilège admin public ' + label)
    check(record['type'] == profile, 'Profil public fixé côté serveur ' + label)
    return session, {'email': email, 'password': password}, record


def verification_url(email):
    messages = requests.get(MAILBOX, timeout=10).json()
    matched = [message for message in messages if email in message['to']]
    if not matched:
        raise AssertionError('E-mail de vérification absent de la boîte SMTP de test')
    match = re.search(r'https?://\S+/verification-email/[a-f0-9]{64}', matched[-1]['text'])
    if not match:
        raise AssertionError('Lien de vérification absent du message')
    return match.group(0)


def verify(session, creds):
    url = verification_url(creds['email'])
    page = session.get(url, timeout=20, allow_redirects=False)
    check(page.status_code == 200 and 'Confirmer mon adresse' in page.text, 'E-mail contient un lien utilisable')
    check(not fixture(creds['email'])['verified'], 'GET seul ne vérifie pas l’e-mail')
    bad = session.post(url, data={'_csrf_token': 'invalide'}, timeout=20, allow_redirects=False)
    check(bad.status_code == 403 and not fixture(creds['email'])['verified'], 'Confirmation CSRF invalide refusée')
    token = inputs(page.text)
    response = session.post(url, data=token, timeout=20, allow_redirects=False)
    check(response.status_code == 200 and fixture(creds['email'])['verified'], 'Confirmation e-mail POST valide')
    check(session.get(url, timeout=20).status_code == 410, 'Lien à usage unique')
    return fixture(creds['email'])


def decide(admin, record, decision, reason=None, csrf_valid=True):
    page = get(admin, '/admin/inscriptions/' + str(record['id']))
    soup = BeautifulSoup(page.text, 'html.parser')
    target = '/admin/inscriptions/' + str(record['id']) + '/' + decision
    form = soup.find('form', attrs={'action': target})
    if form is None:
        raise AssertionError('Formulaire de décision absent')
    values = {x['name']: x.get('value', '') for x in form.find_all('input') if x.get('name')}
    if not csrf_valid:
        values['_csrf_token'] = 'invalide'
    if reason is not None:
        values['motif'] = reason
    return admin.post(BASE + target, data=values, timeout=20, allow_redirects=False)


try:
    data = admin_fixture('create')
    admin = requests.Session()
    check(login(admin, data['credentials']['admin']).status_code == 302, 'Administrateur de test connecté')
    anonymous = requests.Session()
    check(get(anonymous, '/inscription').status_code == 200, 'Choix des trois profils public')
    check(get(anonymous, '/inscription/admin').status_code == 404, 'Aucune inscription admin')
    check(get(anonymous, '/admin/inscriptions').status_code == 302, 'Demandes admin non publiques')

    # Champs supplémentaires ne sont pas mappés en permissions.
    invalid_email = f'registration-{TAG}-invalid@nuru.invalid'
    EMAILS.append(invalid_email)
    page = get(anonymous, '/inscription/apprenant')
    values = inputs(page.text, 'registration')
    password = secrets.token_hex(20)
    values.update({'registration[prenom]': 'Test', 'registration[nom]': 'Invalid', 'registration[email]': invalid_email,
                   'registration[plainPassword][first]': password, 'registration[plainPassword][second]': password,
                   'registration[roles]': 'ROLE_ADMIN'})
    response = anonymous.post(BASE + '/inscription/apprenant', data=values, timeout=20, allow_redirects=False)
    check(response.status_code == 422 and fixture(invalid_email).get('absent'), 'Injection de rôle rejetée sans création de compte')
    values.pop('registration[roles]')
    values['registration[_token]'] = 'invalide'
    response = anonymous.post(BASE + '/inscription/apprenant', data=values, timeout=20, allow_redirects=False)
    check(response.status_code == 422 and fixture(invalid_email).get('absent'), 'Inscription sans CSRF valide refusée')
    page = get(anonymous, '/inscription/apprenant')
    values['registration[_token]'] = inputs(page.text, 'registration')['registration[_token]']
    values['registration[plainPassword][first]'] = 'court'
    values['registration[plainPassword][second]'] = 'court'
    response = anonymous.post(BASE + '/inscription/apprenant', data=values, timeout=20, allow_redirects=False)
    check(response.status_code == 422 and fixture(invalid_email).get('absent'), 'Mot de passe trop court refusé')
    values['registration[plainPassword][first]'] = 'é' * 3000
    values['registration[plainPassword][second]'] = 'é' * 3000
    response = anonymous.post(BASE + '/inscription/apprenant', data=values, timeout=20, allow_redirects=False)
    check(response.status_code == 422 and fixture(invalid_email).get('absent'), 'Mot de passe Unicode trop volumineux refusé sans erreur serveur')
    values['registration[plainPassword][first]'] = secrets.token_hex(20)
    values['registration[plainPassword][second]'] = secrets.token_hex(20)
    response = anonymous.post(BASE + '/inscription/apprenant', data=values, timeout=20, allow_redirects=False)
    check(response.status_code == 422 and fixture(invalid_email).get('absent'), 'Confirmation différente refusée')

    learner, learner_creds, record = register('apprenant', 'learner')
    check(login(learner, learner_creds).status_code == 302 and get(learner, '/espace/apprenant').status_code == 302, 'Connexion apprenant avant e-mail refusée')
    active = verify(learner, learner_creds)
    check(active['active'] and active['status'] == 'dispense', 'Apprenant activé après vérification seule')
    check(login(learner, learner_creds).status_code == 302 and get(learner, '/espace/apprenant').status_code == 200, 'Apprenant vérifié peut se connecter')
    check(get(learner, '/admin/inscriptions').status_code == 403, 'Apprenant ne peut pas examiner les inscriptions')
    # L’adresse déjà inscrite ne crée ni nouveau compte ni changement de profil.
    duplicate = requests.Session()
    page = get(duplicate, '/inscription/apprenant')
    values = inputs(page.text, 'registration')
    values.update({'registration[prenom]': 'Autre', 'registration[nom]': 'Identité', 'registration[email]': learner_creds['email'],
                   'registration[plainPassword][first]': secrets.token_hex(20)})
    values['registration[plainPassword][second]'] = values['registration[plainPassword][first]']
    response = duplicate.post(BASE + '/inscription/apprenant', data=values, timeout=20, allow_redirects=False)
    check(response.status_code == 302 and fixture(learner_creds['email'])['id'] == record['id'], 'Adresse déjà inscrite : réponse générique et compte conservé')

    teacher, teacher_creds, teacher_record = register('formateur', 'teacher')
    verified = verify(teacher, teacher_creds)
    check(not verified['active'] and verified['status'] == 'attente', 'Formateur : e-mail seul insuffisant')
    check(decide(admin, teacher_record, 'approuver', csrf_valid=False).status_code == 403, 'Approbation admin sans CSRF refusée')
    check(fixture(teacher_creds['email'])['status'] == 'attente', 'Demande inchangée après CSRF invalide')
    decision_page = get(admin, '/admin/inscriptions/' + str(teacher_record['id']))
    decision_form = BeautifulSoup(decision_page.text, 'html.parser').find('form', attrs={'action': '/admin/inscriptions/' + str(teacher_record['id']) + '/approuver'})
    decision_token = decision_form.find('input')['value']
    check(decide(admin, teacher_record, 'approuver').status_code == 302, 'Approbation dans l’interface admin')
    teacher_record = fixture(teacher_creds['email'])
    check(teacher_record['active'] and teacher_record['decisionBy'] is not None, 'Formateur activé avec audit après les deux conditions')
    replay = admin.post(BASE + '/admin/inscriptions/' + str(teacher_record['id']) + '/approuver', data={'_csrf_token': decision_token}, timeout=20, allow_redirects=False)
    check(replay.status_code == 302 and fixture(teacher_creds['email'])['status'] == 'approuve', 'Une décision déjà traitée ne modifie pas le dossier')
    check(login(teacher, teacher_creds).status_code == 302 and get(teacher, '/espace/formateur').status_code == 200, 'Formateur approuvé connecté')
    check(get(teacher, '/admin/inscriptions').status_code == 403, 'Formateur ne peut pas examiner les inscriptions')

    institution, institution_creds, institution_record = register('etablissement', 'institution')
    check(institution_record['institution'] == 'Établissement test ' + TAG, 'Établissement relié au compte représentant')
    check(decide(admin, institution_record, 'approuver').status_code == 302, 'Établissement approuvé avant vérification')
    approved = fixture(institution_creds['email'])
    check(not approved['active'] and approved['status'] == 'approuve', 'Approbation seule n’active pas le compte établissement')
    check(login(institution, institution_creds).status_code == 302 and get(institution, '/espace/etablissement').status_code == 302, 'Connexion institution avant e-mail refusée')
    verified = verify(institution, institution_creds)
    check(verified['active'], 'Établissement activé quel que soit l’ordre des validations')
    check(login(institution, institution_creds).status_code == 302 and get(institution, '/espace/etablissement').status_code == 200, 'Espace établissement accessible après activation')
    check(get(institution, '/admin/inscriptions').status_code == 403, 'Établissement n’a aucun privilège admin')

    refused, refused_creds, refused_record = register('formateur', 'refused')
    old_url = verification_url(refused_creds['email'])
    check(decide(admin, refused_record, 'refuser', '').status_code == 302 and fixture(refused_creds['email'])['status'] == 'attente', 'Refus sans motif ne change pas la demande')
    check(decide(admin, refused_record, 'refuser', 'Dossier de test incomplet.').status_code == 302, 'Refus motivé dans l’interface')
    record = fixture(refused_creds['email'])
    check(not record['active'] and record['status'] == 'refuse' and record['reason'] == 'Dossier de test incomplet.', 'Refus conservé avec motif sans activation')
    check(refused.get(old_url, timeout=20).status_code == 410, 'Refus invalide le lien de vérification')
    check(login(refused, refused_creds).status_code == 302 and get(refused, '/espace/formateur').status_code == 302, 'Compte refusé ne peut pas se connecter')

    resend, resend_creds, resend_record = register('apprenant', 'resend')
    first = verification_url(resend_creds['email'])
    fixture(resend_creds['email'], 'resend-ready')
    page = get(resend, '/verification-email/renvoyer')
    vals = inputs(page.text, 'resend_verification')
    vals['resend_verification[email]'] = resend_creds['email']
    response = resend.post(BASE + '/verification-email/renvoyer', data=vals, timeout=20)
    second = verification_url(resend_creds['email'])
    check(response.status_code == 200 and second != first, 'Renvoi génère un nouveau lien')
    check(resend.get(first, timeout=20).status_code == 410, 'Ancien lien invalidé après renvoi')
    # Délai minimum : un renvoi immédiatement répété n’émet pas un troisième lien.
    page = get(resend, '/verification-email/renvoyer')
    vals = inputs(page.text, 'resend_verification')
    vals['resend_verification[email]'] = resend_creds['email']
    resend.post(BASE + '/verification-email/renvoyer', data=vals, timeout=20)
    check(verification_url(resend_creds['email']) == second, 'Délai minimum de renvoi respecté')
    fixture(resend_creds['email'], 'expire')
    check(resend.get(second, timeout=20).status_code == 410, 'Lien expiré refusé')
    page = get(admin, '/admin/inscriptions?statut=tous')
    check(page.status_code == 200 and teacher_creds['email'] in page.text and institution_creds['email'] in page.text, 'Demandes visibles dans la liste admin filtrée')
    check(first not in page.text and second not in page.text and '$2y$' not in page.text, 'Aucun lien de vérification ou hash de mot de passe dans l’admin')
    print(f'{COUNT} contrôles d’inscription réussis.', flush=True)
finally:
    for email in EMAILS:
        fixture(email, 'cleanup')
    admin_fixture('cleanup')
    print('Tous les comptes, établissements et liens temporaires de ce test ont été nettoyés.', flush=True)
