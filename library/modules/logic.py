"""
    :version 0.1
"""
from castor.flow.component import Component
from castor.exception import NoReturnException, ExitException
import re


class Switch(Component):
    def func(self, value):
        """
        :description Permet de tester la valeur passée en entrée et de retourner différentes valeurs en fonction du résultat
        :size 0
        :param value:*:Valeur sur laquelle sont effectués les tests
        :return value:*:Valeur retournée en fonction du résultat des tests
        :setting default:string:Valeur retournée par défaut par le bloc
        :setting cases:json:Définition des différents tests et des résultats associés
        """
        cases = self.settings.get("cases", [])

        for case in cases:

            if case['type'] == 'equals':
                if value == case['test']:
                    return case['value'].format(value=value)

            elif case['type'] == 'greater':
                if float(value) >= float(case['test']):
                    return case['value'].format(value=value)

            elif case['type'] == 'greater_strict':
                if float(value) > float(case['test']):
                    return case['value'].format(value=value)

            elif case['type'] == 'lesser':
                if float(value) <= float(case['test']):
                    return case['value'].format(value=value)

            elif case['type'] == 'lesser_strict':
                if float(value) < float(case['test']):
                    return case['value'].format(value=value)

            elif case['type'] == 'contains':
                if str(case['test']) in str(value):
                    return case['value'].format(value=value)

            elif case['type'] == 'starts_with':
                if str(value).startswith(str(case['test'])):
                    return case['value'].format(value=value)

            elif case['type'] == 'ends_with':
                if str(value).endswith(str(case['test'])):
                    return case['value'].format(value=value)

            elif case['type'] == 'regex':
                if re.search(str(case['test']), str(value)) is not None:
                    return case['value'].format(value=value)

            else:
                raise Exception("Le type de test \"{}\" n'est pas pris en charge".format(case['type']))

        return self.settings.get("default", "").format(value=value)


class Splitter(Component):
    def func(self, value):
        """
        :description Permet d'emprunter différentes branches en fonction d'un test effecuté sur la valeur passée en entrée
        :size 1
        :param value:*:Valeur sur laquelle est effectué le test
        :return true:*:La valeur passée en entrée est transmise sur cette branche si le résultat du test est positif
        :return false:*:La valeur passée en entrée est transmise sur cette branche si le résultat du test est négatif
        :setting case:json:Définition du test
        """
        case = self.settings.get("case", {'type': 'equals', 'test': ''})

        if case['type'] == 'equals':
            return value, None if value == case['test'] else None, value

        elif case['type'] == 'greater':
            return value, None if float(value) >= float(case['test']) else None, value

        elif case['type'] == 'greater_strict':
            return value, None if float(value) > float(case['test']) else None, value

        elif case['type'] == 'lesser':
            return value, None if float(value) <= float(case['test']) else None, value

        elif case['type'] == 'lesser_strict':
            return value, None if float(value) < float(case['test']) else None, value

        elif case['type'] == 'contains':
            return value, None if str(case['test']) in str(value) else None, value

        elif case['type'] == 'starts_with':
            return value, None if str(value).startswith(str(case['test'])) else None, value

        elif case['type'] == 'ends_with':
            return value, None if str(value).endswith(str(case['test'])) else None, value

        elif case['type'] == 'regex':
            return value, None if re.search(str(case['test']), str(value)) is not None else None, value

        else:
            raise Exception("Le type de test \"{}\" n'est pas pris en charge".format(case['type']))

class Comparator(Component):
    def func(self, value1, value2):
        """
        :description Permet d'effectuer une comparaison entre les 2 valeurs passées en entrée
        :size 1
        :param value1:*:Valeur sur laquelle sera effectué le test
        :param value2:*:Valeur avec laquelle le test est effectué
        :return value:*:Valeur retournée en fonction du résultat des tests
        :setting default:*:Valeur retournée par défaut par le bloc
        :setting cases:json:Définition des différents tests et des résultats associés
        """
        cases = self.settings.get("cases", [])

        for case in cases:

            if case['type'] == 'equals':
                if value1 == value2:
                    return case['value'].format(value1=value1, value2=value2)

            elif case['type'] == 'greater':
                if float(value1) >= float(value2):
                    return case['value'].format(value1=value1, value2=value2)

            elif case['type'] == 'greater_strict':
                if float(value1) > float(value2):
                    return case['value'].format(value1=value1, value2=value2)

            elif case['type'] == 'lesser':
                if float(value1) <= float(value2):
                    return case['value'].format(value1=value1, value2=value2)

            elif case['type'] == 'lesser_strict':
                if float(value1) < float(value2):
                    return case['value'].format(value1=value1, value2=value2)

            elif case['type'] == 'contains':
                if str(value2) in str(value1):
                    return case['value'].format(value1=value1, value2=value2)

            elif case['type'] == 'starts_with':
                if str(value1).startswith(str(value2)):
                    return case['value'].format(value1=value1, value2=value2)

            elif case['type'] == 'ends_with':
                if str(value1).endswith(str(value2)):
                    return case['value'].format(value1=value1, value2=value2)

            else:
                raise Exception("Le type de test \"{}\" n'est pas pris en charge".format(case['type']))

        return self.settings.get("default", "").format(value1=value1, value2=value2)


class Assert(Component):
    def func(self, value):
        """
        :description Effectue un test sur la valeur passer en entrer et ne le transmet qu'en cas de succès
        :size 0
        :param value:*:Valeur sur laquelle doit être effectué le test
        :return value:*:Valeur d'entrée
        :setting case:json:Définition du test
        :setting exit:boolean:Définit si le processus doit être arrêté dans le cas ou le test est négatif
        :setting message:string:Message à afficher en cas d'arrêt (possibilité d'afficher l'entrée avec <{value}>)
        """
        case = self.settings.get("case", {'type': 'equals', 'test': ''})


        if case['type'] == 'equals':
            if value == case['test']:
                return value

        elif case['type'] == 'greater':
            if float(value) >= float(case['test']):
                return value

        elif case['type'] == 'greater_strict':
            if float(value) > float(case['test']):
                return value

        elif case['type'] == 'lesser':
            if float(value) <= float(case['test']):
                return value

        elif case['type'] == 'lesser_strict':
            if float(value) < float(case['test']):
                return value

        elif case['type'] == 'contains':
            if str(case['test']) in str(value):
                return value

        elif case['type'] == 'starts_with':
            if str(value).startswith(str(case['test'])):
                return value

        elif case['type'] == 'ends_with':
            if str(value).endswith(str(case['test'])):
                return value

        elif case['type'] == 'regex':
            if re.search(str(case['test']), str(value)) is not None:
                return value

        else:
            raise Exception("Le type de test \"{}\" n'est pas pris en charge".format(case['type']))

        if self.settings.get("exit", 'continue') == 'exit':
            raise ExitException(
                self.settings.get("message", "Test {}({}) négatif sur \"{}\"".format(case['type'], case['test'], value)).format(value=value)
            )
        else:
            raise NoReturnException()
