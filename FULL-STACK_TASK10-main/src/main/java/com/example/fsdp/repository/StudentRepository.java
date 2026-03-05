package com.example.fsdp.repository;

import org.springframework.data.jpa.repository.JpaRepository;
import com.example.fsdp.entity.Student;

public interface StudentRepository extends JpaRepository<Student, Integer> {
}