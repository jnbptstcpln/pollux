"""
    :version 0.1
"""
from castor.flow.component import Component


class Print(Component):
    def func(self, value):
        """
        :param value:string
        """
        print(value)


class Log(Component):
    def func(self, value):
        """
        :param value:string
        """
        self.flow.log(value)

class Sleep(Component):
    def func(self, value):
        """
        :param value:string
        :return value:string
        :setting duration:float
        """
        import time
        time.sleep(float(self.settings.get("duration", "1").replace(',', '.')))
        return value


class Exit(Component):
    def func(self, value):
        """
        :param value:string
        """
        self.exit()


class Concat(Component):
    def func(self, string1, string2):
        """
        :param string1:string
        :param string2:string
        :return result:string
        """
        return string1 + string2


class Format(Component):
    def func(self, value):
        """
        :setting template:string
        :param value:string
        :return result:string
        """
        return self.settings.get("template", "{}").format(value)