#!/usr/bin/env python3
"""Boîte SMTP de test volatile. Aucun message n’est envoyé sur Internet ; localhost uniquement."""
import json
import signal
import threading
from email import policy
from email.parser import BytesParser
from http.server import BaseHTTPRequestHandler, HTTPServer

from aiosmtpd.controller import Controller

MESSAGES = []
LOCK = threading.Lock()


class Handler:
    async def handle_DATA(self, server, session, envelope):
        mail = BytesParser(policy=policy.default).parsebytes(envelope.content)
        text = mail.get_body(preferencelist=('plain',))
        with LOCK:
            MESSAGES.append({'to': envelope.rcpt_tos, 'text': text.get_content() if text else str(mail)})
        return '250 Message conservé uniquement dans la mémoire du test'


class API(BaseHTTPRequestHandler):
    def do_GET(self):
        with LOCK:
            result = list(MESSAGES)
        data = json.dumps(result).encode()
        self.send_response(200)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        self.wfile.write(data)

    def log_message(self, *args):
        pass  # Ne jamais journaliser les liens de vérification.


if __name__ == '__main__':
    controller = Controller(Handler(), hostname='127.0.0.1', port=1026)
    controller.start()
    api = HTTPServer(('127.0.0.1', 1081), API)
    print('SMTP de test 127.0.0.1:1026 ; messages en mémoire sur 127.0.0.1:1081.', flush=True)
    try:
        api.serve_forever()
    finally:
        controller.stop()
        api.server_close()
