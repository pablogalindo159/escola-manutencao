import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/community_provider.dart';
import '../../widgets/post_card.dart';

class PostsScreen extends StatefulWidget {
  final int? courseId;

  const PostsScreen({
    Key? key,
    this.courseId,
  }) : super(key: key);

  @override
  State<PostsScreen> createState() => _PostsScreenState();
}

class _PostsScreenState extends State<PostsScreen> {
  // Filtro por curso (só na aba geral, sem courseId fixo)
  int? _filterCourseId;

  @override
  void initState() {
    super.initState();
    _loadPosts();
  }

  Future<void> _loadPosts() async {
    final provider = context.read<CommunityProvider>();
    await provider.loadPosts(
      courseId: widget.courseId,
      filterCourseId: widget.courseId == null ? _filterCourseId : null,
    );
  }

  void _openPostForm() {
    Navigator.of(context)
        .pushNamed('/post-form', arguments: widget.courseId ?? _filterCourseId)
        .then((result) {
      if (result == true) {
        _loadPosts();
      }
    });
  }

  Widget _buildCourseFilter(CommunityProvider provider) {
    if (widget.courseId != null || provider.courses.length < 2) {
      return const SizedBox.shrink();
    }
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
      child: DropdownButtonFormField<int?>(
        initialValue: _filterCourseId,
        isExpanded: true,
        decoration: const InputDecoration(
          labelText: 'Filtrar por curso',
          border: OutlineInputBorder(),
          contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        ),
        items: [
          const DropdownMenuItem<int?>(
            value: null,
            child: Text('Todos os cursos'),
          ),
          ...provider.courses.map(
            (c) => DropdownMenuItem<int?>(
              value: (c['id'] as num).toInt(),
              child: Text(
                (c['title'] ?? '').toString(),
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ),
        ],
        onChanged: (value) {
          setState(() => _filterCourseId = value);
          _loadPosts();
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Comunidade'),
        elevation: 0,
        backgroundColor: const Color(0xFF0066FF),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_circle_outlined),
            onPressed: _openPostForm,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadPosts,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Container(
                padding: const EdgeInsets.all(24),
                color: const Color(0xFF0066FF),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Comunidade',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                        fontFamily: 'Poppins',
                      ),
                    ),
                    const SizedBox(height: 4),
                    const Text(
                      'Compartilhe dúvidas e experiências',
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.white70,
                        fontFamily: 'Inter',
                      ),
                    ),
                  ],
                ),
              ),

              // Filtro por curso
              Consumer<CommunityProvider>(
                builder: (context, provider, _) => _buildCourseFilter(provider),
              ),

              // Posts List
              Padding(
                padding: const EdgeInsets.all(16),
                child: Consumer<CommunityProvider>(
                  builder: (context, provider, _) {
                    if (provider.isLoading) {
                      return ListView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: 3,
                        itemBuilder: (context, index) {
                          return const Padding(
                            padding: EdgeInsets.only(bottom: 16),
                            child: PostShimmer(),
                          );
                        },
                      );
                    }

                    if (provider.errorMessage != null && provider.posts.isEmpty) {
                      return Center(
                        child: Padding(
                          padding: const EdgeInsets.all(32),
                          child: Column(
                            children: [
                              Icon(Icons.error_outline, size: 64, color: Colors.red[300]),
                              const SizedBox(height: 16),
                              Text(
                                provider.errorMessage!.replaceFirst('Exception: ', ''),
                                textAlign: TextAlign.center,
                                style: TextStyle(fontSize: 15, color: Colors.grey[700]),
                              ),
                              const SizedBox(height: 16),
                              OutlinedButton(
                                onPressed: _loadPosts,
                                child: const Text('Tentar novamente'),
                              ),
                            ],
                          ),
                        ),
                      );
                    }

                    if (widget.courseId == null && provider.courses.isEmpty) {
                      return Center(
                        child: Padding(
                          padding: const EdgeInsets.all(32),
                          child: Text(
                            'Inscreva-se em um curso para participar da comunidade.',
                            textAlign: TextAlign.center,
                            style: TextStyle(fontSize: 16, color: Colors.grey[600]),
                          ),
                        ),
                      );
                    }

                    if (provider.posts.isEmpty) {
                      return Center(
                        child: Padding(
                          padding: const EdgeInsets.all(32),
                          child: Column(
                            children: [
                              Icon(
                                Icons.people_outline,
                                size: 64,
                                color: Colors.grey[300],
                              ),
                              const SizedBox(height: 16),
                              Text(
                                'Nenhum post ainda',
                                style: TextStyle(
                                  fontSize: 16,
                                  color: Colors.grey[600],
                                  fontFamily: 'Inter',
                                ),
                              ),
                              const SizedBox(height: 24),
                              ElevatedButton.icon(
                                onPressed: _openPostForm,
                                icon: const Icon(Icons.add),
                                label: const Text('Criar novo post'),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF0066FF),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }

                    return ListView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: provider.posts.length,
                      itemBuilder: (context, index) {
                        final post = provider.posts[index];
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 16),
                          child: PostCard(
                            post: post,
                            onTap: () {
                              Navigator.of(context)
                                  .pushNamed(
                                    '/post-detail',
                                    arguments: post.id,
                                  )
                                  .then((result) {
                                if (result == true) {
                                  _loadPosts();
                                }
                              });
                            },
                          ),
                        );
                      },
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// Shimmer Loading Widget
class PostShimmer extends StatelessWidget {
  const PostShimmer({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 180,
      decoration: BoxDecoration(
        color: Colors.grey[200],
        borderRadius: BorderRadius.circular(12),
      ),
    );
  }
}
