"""
    :version 0.1
"""
from ..component import Component


class Equals(Component):
    def func(self, a, b):
        """
        :param a:float
        :param b:float
        :return a(true):float
        :return a(false):float
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

class Greater(Component):
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