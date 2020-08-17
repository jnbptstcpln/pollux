"""
    :version 0.1
"""
from castor.flow.component import Component


class Equals(Component):
    def func(self, a, b):
        """
        :param a:float
        :param b:float
        :return a(true):float
        :return a(false):float
        :size 1
        :description
        """
        if a==b:
            return a, None
        else:
            return None, a

class Greater(Component):
    def func(self, a, b):
        """
        :param a:float
        :param b:float
        :return a(true):float
        :return a(false):float
        """
        if a>b:
            return a, None
        else:
            return None, a

class Lesser(Component):
    def func(self, a, b):
        """
        :param a:float
        :param b:float
        :return a(true):float
        :return a(false):float
        """
        if a<b:
            return a, None
        else:
            return None, a


class Contains(Component):
    def func(self, a, b):
        """
        :param a:string
        :param b:string
        :return a(true):float
        :return a(false):float
        """
        if b in a:
            return a, None
        else:
            return None, a

class StartsWith(Component):
    def func(self, a, b):
        """
        :param a:string
        :param b:string
        :return a(true):float
        :return a(false):float
        """
        if a.startswith(b):
            return a, None
        else:
            return None, a

class EndsWith(Component):
    def func(self, a, b):
        """
        :param a:string
        :param b:string
        :return a(true):float
        :return a(false):float
        """
        if a.endswith(b):
            return a, None
        else:
            return None, a