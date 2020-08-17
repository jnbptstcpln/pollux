

import requests
import json


class jAPI:

    endpoint = "http://automate-etc.si.francetelecom.fr/v2"
    key = None

    @classmethod
    def get(cls, request, data):
        payload = data
        payload['request'] = request
        if cls.key:
            payload['key'] = cls.key
        rep = requests.get(cls.endpoint, data=payload)
        try:
            return rep.json()
        except Exception:
            raise Exception("La réponse du serveur est incorrecte...")

    @classmethod
    def post(cls, request, data):
        payload = data
        payload['request'] = request
        if cls.key:
            payload['key'] = cls.key
        rep = requests.post(cls.endpoint, params=payload)
        try:
            return rep.json()
        except Exception:
            raise Exception("La réponse du serveur est incorrecte...")


class OceaneAPI(jAPI):

    endpoint = "http://automate-etc.si.francetelecom.fr/v2/api/oceane/index.php"
    key = "bp6qo324Nb3VW6OvzB6LtSNE6hxBj0OkUm7PA3IWGN"

    @classmethod
    def search_resource(cls, idt11, idt21, idt31, domain="", type=""):
        return cls.get("search", {
            'idt11': idt11,
            'idt21': idt21,
            'idt31': idt31,
            'domain': domain,
            'type': type
        })

    @classmethod
    def search_resource(cls, idt11, idt21, idt31, domain="", type=""):
        return cls.get("search_one", {
            'idt11': idt11,
            'idt21': idt21,
            'idt31': idt31,
            'domain': domain,
            'type': type
        })


class Minerve2(jAPI):

    endpoint = "http://automate-etc.si.francetelecom.fr/v2/api/minerve2/index.php"
    key = "XX"

    @classmethod
    def send(cls, commutateur, commande, infos="", data={}):
        return cls.post("send", {
            'commutateur': commutateur,
            'commande': commande,
            'infos': infos,
            'data': json.dumps(data)
        })

    @classmethod
    def fetch(cls, identifier):
        return cls.get("fetch", {
            'identifier': identifier
        })
