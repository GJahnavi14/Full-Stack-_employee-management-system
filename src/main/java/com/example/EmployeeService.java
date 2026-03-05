package com.example;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;

@Component
public class EmployeeService {

    @Autowired
    private EmployeeRepository repository;

    public void createEmployee(int id, String name, double salary) {
        Employee emp = new Employee(id, name, salary);
        repository.addEmployee(emp);
    }

    public void displayEmployees() {
        for (Employee emp : repository.getAllEmployees()) {
            System.out.println(emp);
        }
    }
}
