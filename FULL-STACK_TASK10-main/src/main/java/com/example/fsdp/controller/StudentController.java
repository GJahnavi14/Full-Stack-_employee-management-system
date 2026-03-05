package com.example.fsdp.controller;

import java.util.List;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

import com.example.fsdp.entity.Student;
import com.example.fsdp.repository.StudentRepository;

@RestController
public class StudentController {

    @Autowired
    private StudentRepository repo;

    // CREATE
    @PostMapping("/create")
    public Student create(@RequestBody Student s) {
        return repo.save(s);
    }

    // READ (All)
    @GetMapping("/read")
    public List<Student> readAll() {
        return repo.findAll();
    }

    // READ (By ID)
    @GetMapping("/read/{id}")
    public Student readById(@PathVariable int id) {
        return repo.findById(id).orElse(null);
    }

    // UPDATE (Without ID in URL)
    @PutMapping("/update")
    public Student update(@RequestBody Student s) {
        return repo.save(s);
    }

    // UPDATE (With ID in URL)
    @PutMapping("/update/{id}")
    public Student updateById(@PathVariable int id, @RequestBody Student s) {
        s.setId(id);
        return repo.save(s);
    }

    // DELETE (All)
    @DeleteMapping("/delete")
    public String deleteAll() {
        repo.deleteAll();
        return "All students deleted";
    }

    // DELETE (By ID)
    @DeleteMapping("/delete/{id}")
    public String deleteById(@PathVariable int id) {
        repo.deleteById(id);
        return "Student deleted with ID: " + id;
    }
}