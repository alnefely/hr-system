<?php

return [
    'title'         => 'Employee Skills',
    'singular'      => 'Skill',
    'add_new'       => 'Add New Skill',
    'edit'          => 'Edit Skill Details',
    'trash'         => 'Skills Trash',
    'back'          => 'Back to Employee Profile',
    'core'          => 'Core Skill',
    'secondary'     => 'Secondary Skill',

    'fields' => [
        'employee'          => 'Employee',
        'skill_name'        => 'Skill Name',
        'proficiency_level' => 'Proficiency Level',
        'years_experience'  => 'Years of Experience',
        'is_core'           => 'Core Specialization Skill',
        'evaluation_source' => 'Evaluation Source',
        'created_at'        => 'Logged At',
        'deleted_at'        => 'Deleted At',
    ],

    'levels' => [
        'beginner'     => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced'     => 'Advanced',
        'expert'       => 'Expert',
    ],

    'sources' => [
        'self'    => 'Self Assessment',
        'manager' => 'Manager Assessment',
    ],

    'messages' => [
        'created'       => 'Skill added successfully',
        'updated'       => 'Skill updated successfully',
        'deleted'       => 'Skill moved to trash successfully',
        'restored'      => 'Skill restored successfully',
        'force_deleted' => 'Skill permanently deleted',
        'empty_trash'   => 'Skills trash is empty',
    ],

    // شرح الحقول (أيقونة ؟ جنب العنوان)
    'hints' => [
        'proficiency_level' => 'How well the employee masters the skill — for evaluation and reports.',
        'evaluation_source' => 'Where this rating came from (test, manager, certificate…) to judge how reliable it is.',
        'is_core'           => 'A skill essential to the employee’s job — highlighted in their file.',
    ],

];
