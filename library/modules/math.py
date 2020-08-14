"""
    :version 0.1
"""
from castor.flow.component import Component


class Addition(Component):
    def func(self, a, b):
        """
        :param a:float
        :param b:float
        :return a+b:float
        """
        return a+b


class Multiplication(Component):
    def func(self, a, b):
        """
        :param a:float
        :param b:float
        :return a*b:float
        """
        return a*b

class Substraction(Component):
    def func(self, a, b):
        """
        :param a:float
        :param b:float
        :return a-b:float
        """
        return a-b

class Division(Component):
    def func(self, a, b):
        """
        :param a:float
        :param b:float
        :return a/b:float
        """
        return a/b