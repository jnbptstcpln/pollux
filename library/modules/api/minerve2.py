"""
    :version 0.1
"""
import time
from castor.flow.component import Component


class RHM(Component):
    def func(self, sgtqs, commande):
        """
        :description Permet d'exécuter une RHM puis d'en analyser le résultat
        :size 2
        :require japi
        :param sgtqs:string:Identifiant du commutateur
        :param commande:string:Commande à exécuter
        :return output:string:Sortie standard de Minerve2
        :return raw:string:Sortie brute du terminal
        """
        from castor.lib.japi import Minerve2
        rep = Minerve2.send(sgtqs, commande)

        if not rep['success']:
            raise Exception(rep['message'])

        identifier = rep['payload']['identifier']

        while True:
            rep = Minerve2.fetch(identifier)
            if rep['success']:
                payload = rep['payload']
                if payload['completed']:
                    return payload['output'], payload['pec_output']
            time.sleep(2)

class ALAIL_NEAL(Component):
    def func(self, sgtqs, neal):
        """
        :description Vérifie la présence d'une alarme en utilisant son NEAL
        :size 2
        :require japi
        :param sgtqs:string:Identifiant du commutateur
        :param neal:string:NEAL de l'alarme
        :return output:string:Sortie <Alarme présente> ou <Aucune alarme>
        :return raw:string:Sortie brute du terminal
        """
        from castor.lib.japi import Minerve2
        rep = Minerve2.send(sgtqs, "ALAIL:N={}".format(neal))

        if not rep['success']:
            raise Exception(rep['message'])

        identifier = rep['payload']['identifier']

        while True:
            rep = Minerve2.fetch(identifier)
            if rep['success']:
                payload = rep['payload']
                if payload['completed']:
                    return payload['output'], payload['pec_output']
            time.sleep(2)
