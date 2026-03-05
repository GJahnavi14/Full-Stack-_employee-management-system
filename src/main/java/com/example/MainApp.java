package com.example;

import org.springframework.beans.factory.BeanFactory;
import org.springframework.context.annotation.AnnotationConfigApplicationContext;

public class MainApp {

    public static void main(String[] args) {

        BeanFactory factory =
                new AnnotationConfigApplicationContext(AppConfig.class);

        EmployeeService service =
                factory.getBean(EmployeeService.class);

        service.createEmployee(1, "Jahnavi", 50000);
        service.createEmployee(2, "Ravi", 60000);
        service.createEmployee(3, "pooja",800000);
        service.createEmployee(4, "govardhini",70000);
        service.createEmployee(5, "navya", 90000);

        service.displayEmployees();
    }
}
